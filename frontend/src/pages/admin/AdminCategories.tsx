import { useState, useEffect } from 'react';
import { Plus, Edit, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { getProducts, Product } from '@/data/products';
import { categoriesApi, Category } from '@/data/categories';
import { useToast } from '@/hooks/use-toast';

const AdminCategories = () => {
  const { toast } = useToast();
  const [categoryList, setCategoryList] = useState<Category[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<Category | null>(null);
  const [form, setForm] = useState({ name: '' });

  useEffect(() => {
    const loadData = async () => {
      try {
        const [categoriesData, productsData] = await Promise.all([
          categoriesApi.getAll(),
          getProducts()
        ]);
        setCategoryList(categoriesData);
        setProducts(productsData);
      } catch (error) {
        console.error('Failed to load data:', error);
        toast({ title: 'Ошибка загрузки данных', variant: 'destructive' });
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, [toast]);

  const getProductCount = (categoryId: number) => {
    return products.filter(p => String(p.category) === String(categoryId)).length;
  };

  const resetForm = () => {
    setForm({ name: '' });
    setEditingCategory(null);
  };

  const handleEdit = (category: Category) => {
    setEditingCategory(category);
    setForm({ name: category.name });
    setIsDialogOpen(true);
  };

  const loadCategories = async () => {
    try {
      // Загружаем с принудительным обновлением (без кеша)
      const categoriesData = await categoriesApi.getAll(false, true);
      setCategoryList(categoriesData);
    } catch (error) {
      console.error('Failed to load categories:', error);
    }
  };

  const handleSave = async () => {
    try {
      if (editingCategory) {
        // Обновление категории
        await categoriesApi.update(editingCategory.id, { name: form.name });
        await loadCategories(); // Перезагружаем список категорий
        toast({ title: 'Категория обновлена' });
      } else {
        // Создание новой категории
        await categoriesApi.create({ name: form.name });
        await loadCategories(); // Перезагружаем список категорий
        toast({ title: 'Категория добавлена' });
      }
      setIsDialogOpen(false);
      resetForm();
    } catch (error: any) {
      toast({ 
        title: 'Ошибка', 
        description: error.message || 'Не удалось сохранить категорию', 
        variant: 'destructive' 
      });
    }
  };

  const handleDelete = async (id: number) => {
    if (getProductCount(id) > 0) {
      toast({ 
        title: 'Невозможно удалить', 
        description: 'В категории есть товары', 
        variant: 'destructive' 
      });
      return;
    }
    
    try {
      await categoriesApi.delete(id);
      await loadCategories(); // Перезагружаем список категорий
      toast({ title: 'Категория удалена' });
    } catch (error: any) {
      toast({ 
        title: 'Ошибка удаления', 
        description: error.message || 'Не удалось удалить категорию', 
        variant: 'destructive' 
      });
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold">Категории</h1>
          <p className="text-muted-foreground">Управление категориями товаров</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if (!open) resetForm(); }}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              Добавить категорию
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{editingCategory ? 'Редактировать категорию' : 'Новая категория'}</DialogTitle>
            </DialogHeader>
            <div className="grid gap-4 py-4">
              <div className="space-y-2">
                <Label>Название *</Label>
                <Input 
                  value={form.name} 
                  onChange={(e) => setForm({ ...form, name: e.target.value })} 
                  placeholder="Название категории"
                />
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setIsDialogOpen(false)}>Отмена</Button>
              <Button onClick={handleSave} disabled={!form.name}>
                {editingCategory ? 'Сохранить' : 'Добавить'}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      {loading ? (
        <Card>
          <CardContent className="p-12 text-center">
            <p className="text-muted-foreground">Загрузка категорий...</p>
          </CardContent>
        </Card>
      ) : categoryList.length === 0 ? (
        <Card>
          <CardContent className="p-12 text-center">
            <p className="text-muted-foreground">Категории не найдены</p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {categoryList.map((category) => (
            <Card key={category.id} className="group">
              <CardContent className="p-6">
                <div className="flex items-start justify-between">
                  <div>
                    <h3 className="font-semibold text-lg">{category.name}</h3>
                    <Badge variant="secondary" className="mt-2">
                      {getProductCount(category.id)} товаров
                    </Badge>
                  </div>
                  <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <Button variant="ghost" size="icon" onClick={() => handleEdit(category)}>
                      <Edit className="h-4 w-4" />
                    </Button>
                    <Button 
                      variant="ghost" 
                      size="icon" 
                      className="text-destructive"
                      onClick={() => handleDelete(category.id)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
};

export default AdminCategories;
