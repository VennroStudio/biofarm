<?php

declare(strict_types=1);

namespace App\Modules\Product\Service;

use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use RuntimeException;

/** Explicit, reviewed one-time catalogue data update. No schema or financial changes. */
final readonly class CatalogPackagingUpdate
{
    public const string MARKER = 'catalog-packaging:2026-10-28';
    public const array TABLES = ['products','product_groups','product_group_items','product_images','product_attribute_values','attribute_values','product_blog_posts','faq_items','material_placements','material_target_selections'];

    public function __construct(private Connection $db, private string $publicPath, private array $manifest) {}

    public function preview(): array
    {
        if ($this->applied()) return ['already_applied'=>true];
        $this->validate();
        $variants=array_merge(...array_column($this->manifest['families'],'variants'));
        return ['already_applied'=>false,'groups'=>count($this->manifest['families']),'reviewed_cards'=>count($variants),'new_cards'=>count(array_filter($variants,static fn(array $v):bool=>$v['existing_id']===null)),'skipped_products'=>$this->manifest['skip_product_ids'],'skipped_price_rows'=>$this->manifest['skip_source_rows']];
    }

    public function apply(): array
    {
        if ((int)$this->db->fetchOne('SELECT GET_LOCK(?,0)',[self::MARKER])!==1) throw new RuntimeException('Catalogue update already running');
        try {
            return $this->db->transactional(function (): array {
                if ($this->applied()) return ['already_applied'=>true];
                $this->db->fetchAllAssociative('SELECT id FROM products WHERE deleted_at IS NULL FOR UPDATE');
                $summary=$this->preview();
                $now=gmdate('Y-m-d H:i:s');
                // Make shared FAQ quantity-neutral instead of duplicating material records per pack.
                foreach ($this->manifest['faq_neutral'] as $faq) {
                    $this->db->update('faq_items',['question'=>$faq['question'],'answer'=>$faq['answer'],'updated_at'=>$now],['id'=>$faq['id']]);
                }
                foreach ($this->manifest['families'] as $family) {
                    $base=$family['base_id']===null ? null : $this->db->fetchAssociative('SELECT * FROM products WHERE id=?',[$family['base_id']]);
                    // Reuse the reviewed Osina group; detach Mix into its own group below.
                    if ($family['base_id']===34) {
                        $group=2;
                        $this->db->update('product_groups',['name'=>$family['name'],'updated_at'=>$now],['id'=>$group]);
                    } else {
                        $this->db->insert('product_groups',['name'=>$family['name'],'created_at'=>$now]);
                        $group=(int)$this->db->lastInsertId();
                    }
                    foreach ($family['variants'] as $variant) {
                        $existing=$variant['existing_id']!==null;
                        $row=$base ?? $this->emptyProduct($family,$now);
                        unset($row['id']);
                        if (!$existing) {
                            $row['created_at']=$now;$row['published_at']=null;
                            $row['sku']=null;$row['gtin']=null;$row['wb_link']=null;$row['ozon_link']=null;
                            $row['badge']=null;
                        }
                        $row['slug']=$variant['slug'];$row['weight']=$variant['weight'];
                        $row['price']=$variant['price'];$row['old_price']=null;
                        $row['is_active']=$variant['active'] ? 1 : 0;$row['updated_at']=$now;
                        if ($base!==null && $family['old_quantity']!==null) {
                            foreach (['description','short_description','features','h1','seo_title','seo_description','image_alt'] as $field) {
                                if ($row[$field]!==null) $row[$field]=$this->packText($row[$field],$family,$variant['quantity']);
                            }
                        }
                        if (isset($variant['photo'])) {
                            $row['image']=$variant['photo']['path'];$row['images']=json_encode([$row['image']],JSON_THROW_ON_ERROR);
                            $row['image_alt']=$family['name'].' — '.$variant['weight'];
                        } elseif (!$existing) {
                            // No supplied matching photograph: do not reuse a different pack's label.
                            $row['image']='';$row['images']='[]';$row['image_alt']=null;
                        }
                        if ($existing) {
                            $id=$variant['existing_id'];$this->db->update('products',$row,['id'=>$id]);
                        } else {
                            $this->db->insert('products',$row);$id=(int)$this->db->lastInsertId();
                            if ($base!==null) $this->copyRelations((int)$base['id'],$id,$family,$variant);
                        }
                        $this->syncPackAttribute($id,$family,$variant,$now);
                        $this->db->delete('product_group_items',['product_id'=>$id]);
                        $this->db->insert('product_group_items',['group_id'=>$group,'product_id'=>$id,'created_at'=>$now]);
                        if (isset($variant['photo'])) {
                            $photo=$variant['photo'];
                            $this->db->delete('product_images',['product_id'=>$id]);
                            $this->db->insert('product_images',['product_id'=>$id,'path'=>$photo['path'],'alt'=>$row['image_alt'],'title'=>$family['name'],'sort_order'=>0,'is_main'=>1,'width'=>$photo['width'],'height'=>$photo['height'],'mime_type'=>'image/jpeg','size'=>$photo['size'],'created_at'=>$now]);
                        }
                    }
                }
                $this->db->insert('program_locks',['id'=>self::MARKER,'payload'=>json_encode(['version'=>$this->manifest['version'],'source'=>$this->manifest['source']],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
                return $summary;
            });
        } finally {
            $this->db->fetchOne('SELECT RELEASE_LOCK(?)',[self::MARKER]);
        }
    }

    private function applied(): bool
    {
        return $this->db->fetchOne('SELECT id FROM program_locks WHERE id=?',[self::MARKER])!==false;
    }

    private function validate(): void
    {
        if (($this->manifest['version']??'')!=='2026-10-28-packaging-v1') throw new InvalidArgumentException('Unknown manifest');
        $slugs=[];$rows=[];$ids=[];
        foreach ($this->manifest['families'] as $family) {
            if ($family['base_id']!==null) {
                if (in_array($family['base_id'],$this->manifest['skip_product_ids'],true)) throw new InvalidArgumentException('Skipped product in manifest');
                $base=$this->db->fetchAssociative('SELECT * FROM products WHERE id=? AND deleted_at IS NULL',[$family['base_id']]);
                if (!$base || $base['name']!==$family['expected_name'] || $base['slug']!==$family['expected_slug']) throw new InvalidArgumentException('Reviewed identity changed: '.$family['base_id']);
                $ids[]=(int)$base['id'];
                $membership=$this->db->fetchOne('SELECT group_id FROM product_group_items WHERE product_id=?',[$base['id']]);
                if ($membership!==false && !((int)$membership===2 && in_array((int)$base['id'],[34,35],true))) throw new InvalidArgumentException('Unexpected existing product group');
            }
            foreach ($family['variants'] as $v) {
                if (isset($slugs[$v['slug']]) || $v['price']<0 || ($v['active'] && $v['price']===0)) throw new InvalidArgumentException('Invalid card / price');
                $slugs[$v['slug']]=true;
                if ($v['existing_id']!==null && $v['existing_id']!==$family['base_id']) throw new InvalidArgumentException('Existing pack must retain source ID');
                if ($v['source_row']!==null) {
                    if (isset($rows[$v['source_row']]) || in_array($v['source_row'],$this->manifest['skip_source_rows'],true)) throw new InvalidArgumentException('Repeated or excluded source row');
                    $rows[$v['source_row']]=true;
                }
                if ($v['existing_id']===null && $this->db->fetchOne('SELECT id FROM products WHERE slug=?',[$v['slug']])!==false) throw new InvalidArgumentException('New slug already exists: '.$v['slug']);
                if (isset($v['photo'])) {
                    $p=$v['photo'];$path=$this->publicPath.$p['path'];
                    if (!str_starts_with($p['path'],'/uploads/products/mockups-20261028/') || str_contains($p['path'],'..') || !is_file($path) || hash_file('sha256',$path)!==$p['sha256']) throw new InvalidArgumentException('Missing or changed matching photo: '.$v['slug']);
                } elseif ($v['existing_id']===null && $v['active']) throw new InvalidArgumentException('New card without photo must stay hidden');
            }
        }
        sort($ids);
        if ($ids!==[9,10,13,14,15,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35] || count($rows)!==85 || count($slugs)!==93) throw new InvalidArgumentException('Manifest coverage changed');
        $members=array_map('intval',$this->db->fetchFirstColumn('SELECT product_id FROM product_group_items WHERE group_id=2 ORDER BY product_id'));
        if ($members!==[34,35]) throw new InvalidArgumentException('Reviewed mixed group changed');
        foreach ($this->manifest['faq_neutral'] as $faq) if ($this->db->fetchOne('SELECT id FROM faq_items WHERE id=?',[$faq['id']])===false) throw new InvalidArgumentException('Missing shared FAQ');
    }

    private function packText(string $text,array $family,int $quantity): string
    {
        $suffix=$family['unit']==='капсул' ? '(\s+(?:желатиновых\s+)?капсул)' : '(\s*'.preg_quote($family['unit'],'~').'\b)';
        return preg_replace_callback('~(?<!\d)'.$family['old_quantity'].$suffix.'~u',static fn(array $m):string=>$quantity.$m[1],$text) ?? $text;
    }

    private function syncPackAttribute(int $product,array $family,array $variant,string $now): void
    {
        if (!in_array($family['unit'],['капсул','мл'],true)) return;
        $attribute=$family['unit']==='капсул' ? 18 : 13;
        $suffix=$attribute===18 ? 'sht' : 'ml';
        $slug=$variant['quantity'].'-'.$suffix;
        $name=$variant['quantity'].($attribute===18 ? ' шт.' : ' мл');
        $value=$this->db->fetchAssociative('SELECT id,name,deleted_at FROM attribute_values WHERE attribute_id=? AND slug=?',[$attribute,$slug]);
        if ($value===false) {
            $this->db->insert('attribute_values',['attribute_id'=>$attribute,'slug'=>$slug,'name'=>$name,'is_indexable'=>0,'sort_order'=>$variant['quantity'],'created_at'=>$now]);
            $valueId=(int)$this->db->lastInsertId();
        } else {
            if ($value['name']!==$name || $value['deleted_at']!==null) throw new InvalidArgumentException('Pack attribute conflict: '.$slug);
            $valueId=(int)$value['id'];
        }
        $this->db->executeStatement('DELETE pav FROM product_attribute_values pav INNER JOIN attribute_values av ON av.id=pav.attribute_value_id WHERE pav.product_id=? AND av.attribute_id=?',[$product,$attribute]);
        $this->db->insert('product_attribute_values',['product_id'=>$product,'attribute_value_id'=>$valueId,'sort_order'=>20]);
    }

    private function emptyProduct(array $family,string $now): array
    {
        return ['name'=>$family['name'],'category_id'=>$family['unit']==='капсул' ? '4' : '2','price'=>0,'image'=>'','weight'=>'','description'=>'','short_description'=>null,'images'=>'[]','features'=>'[]','created_at'=>$now,'updated_at'=>$now,'deleted_at'=>null,'h1'=>$family['name'],'seo_title'=>null,'seo_description'=>null,'image_alt'=>null,'sku'=>null,'gtin'=>null,'wb_link'=>null,'ozon_link'=>null,'badge'=>null,'availability'=>'in_stock','is_active'=>0,'published_at'=>null,'legal_status'=>'unknown'];
    }

    private function copyRelations(int $source,int $target,array $family,array $variant): void
    {
        foreach (['product_attribute_values','product_blog_posts'] as $table) {
            foreach ($this->db->fetchAllAssociative('SELECT * FROM '.$table.' WHERE product_id=?',[$source]) as $row) {
                unset($row['id']);$row['product_id']=$target;
                if ($table==='product_attribute_values' && $row['value_text']!==null) $row['value_text']=$this->packText($row['value_text'],$family,$variant['quantity']);
                $this->db->insert($table,$row);
            }
        }
        foreach (['material_target_selections','material_placements'] as $table) {
            foreach ($this->db->fetchAllAssociative('SELECT * FROM '.$table.' WHERE target_type=? AND target_id=?',['product',(string)$source]) as $row) {
                // These three questions explicitly describe the old bottle's packaging.
                if ($table==='material_placements' && $row['kind']==='faq' && in_array((int)$row['material_id'],[37,48,56],true)) continue;
                $row['target_id']=(string)$target;$this->db->insert($table,$row);
            }
        }
    }
}
