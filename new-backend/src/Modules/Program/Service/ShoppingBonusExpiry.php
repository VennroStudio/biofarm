<?php

declare(strict_types=1);
namespace App\Modules\Program\Service;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;

/** Called under the finance mutex. Replays spending so expiry never removes already spent or reserved bonuses twice. */
final readonly class ShoppingBonusExpiry
{
    public function __construct(private Connection $db) {}
    public static function deadline(string $created, int $months): string
    {
        $date=new DateTimeImmutable($created,new DateTimeZone('UTC'));
        $month=$date->modify('first day of this month')->modify('+'.$months.' months');
        return $month->setDate((int)$month->format('Y'),(int)$month->format('m'),min((int)$date->format('d'),(int)$month->format('t')))->format('Y-m-d H:i:s');
    }
    public function refresh(int $user, string $now): array
    {
        $rows=$this->db->fetchAllAssociative("SELECT * FROM program_ledger WHERE user_id=? AND wallet='shopping' AND state<>'void' ORDER BY created_at,id",[$user]);
        foreach ($rows as &$row) $row['meta']=json_decode($row['details'],true)?:[];
        unset($row);
        usort($rows,static fn($a,$b)=>[$a['created_at'], $a['meta']['sequence']??0, $a['id']] <=> [$b['created_at'], $b['meta']['sequence']??0, $b['id']]);
        // Cancelled source credits must also cancel their derived expiration entries.
        foreach ($rows as $row) {
            if ($row['kind'] !== 'expiration') continue;
            $source = $row['meta']['sourceCreditId'] ?? null;
            if ($source && $this->db->fetchOne('SELECT state FROM program_ledger WHERE id=?', [$source]) === 'void') {
                $this->db->update('program_ledger', ['state' => 'void'], ['id' => $row['id']]);
            }
        }
        $credits=[]; $debt=0;
        foreach ($rows as $row) {
            if ($row['kind']==='expiration') continue;
            $amount=(int)$row['amount_minor'];
            if ($amount>0) {
                $offset=\in_array($row['state'],['available','reserved'],true)?min($debt,$amount):0;
                $debt-=$offset;
                $credits[$row['id']]=['row'=>$row,'remaining'=>$amount-$offset,'expiry'=>$row['meta']['expiresAt']??null];
                continue;
            }
            $left=-$amount;
            $source=$row['meta']['sourceCreditId']??null;
            $keys=array_keys($credits);
            usort($keys,static fn($a,$b)=>[$a===$source?0:1,$credits[$a]['expiry']??'9999',$a]<=>[$b===$source?0:1,$credits[$b]['expiry']??'9999',$b]);
            foreach ($keys as $key) {
                $credit=&$credits[$key]; $origin=$credit['row'];
                $isSource=$source===$key;
                $usable=$isSource || (\in_array($origin['state'],['available','reserved'],true)
                    && ($origin['available_at']===null || $origin['available_at']<=$row['created_at'])
                    && ($credit['expiry']===null || $credit['expiry']>$row['created_at']));
                if ($usable) { $take=min($left,$credit['remaining']); $credit['remaining']-=$take; $left-=$take; }
                unset($credit);
                if ($left===0) break;
            }
            if (\in_array($row['state'],['available','reserved'],true)) $debt+=$left;
        }
        $next=null;$nextMinor=0;
        foreach ($credits as $key=>$credit) {
            if ($credit['expiry']===null) continue; // Existing credits retain their original no-expiry conditions.
            $row=$credit['row'];
            if ($credit['expiry']<=$now) {
                $id='expire:'.(strlen($key) > 90 ? hash('sha256', $key) : $key);
                $existing=$this->db->fetchOne('SELECT id FROM program_ledger WHERE id=?',[$id]);
                $data=['amount_minor'=>-$credit['remaining'],'state'=>$row['state']==='pending'?'pending':'available','available_at'=>$row['available_at']];
                if ($existing!==false) $this->db->update('program_ledger',$data,['id'=>$id]);
                elseif ($credit['remaining']>0) $this->db->insert('program_ledger',$data+['id'=>$id,'user_id'=>$user,'wallet'=>'shopping','kind'=>'expiration','order_id'=>$row['order_id'],'created_at'=>$credit['expiry'],'details'=>json_encode(['sourceCreditId'=>$key,'expiresAt'=>$credit['expiry']],JSON_THROW_ON_ERROR)]);
            } elseif ($credit['remaining']>0 && \in_array($row['state'],['available','reserved'],true)) {
                if ($next===null || $credit['expiry']<$next) { $next=$credit['expiry']; $nextMinor=0; }
                if ($credit['expiry']===$next) $nextMinor+=$credit['remaining'];
            }
        }
        return ['nextExpiryAt'=>$next,'nextExpiryMinor'=>$nextMinor];
    }
}
