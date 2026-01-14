import { useState } from 'react';
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
import { categories as initialCategories, products } from '@/data/products';
import { useToast } from '@/hooks/use-toast';

interface Category {
  id: string;
  label: string;
}

const AdminCategories = () => {
  const { toast } = useToast();
  const [categoryList, setCategoryList] = useState<Category[]>(
    initialCategories.filter(c => c.id !== 'all')
  );
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<Category | null>(null);
  const [form, setForm] = useState({ label: '' });

  const getProductCount = (categoryId: string) => {
    return products.filter(p => p.category === categoryId).length;
  };

  const resetForm = () => {
    setForm({ label: '' });
    setEditingCategory(null);
  };

  const handleEdit = (category: Category) => {
    setEditingCategory(category);
    setForm({ label: category.label });
    setIsDialogOpen(true);
  };

  const handleSave = () => {
    if (editingCategory) {
      setCategoryList(prev => prev.map(c => 
        c.id === editingCategory.id 
          ? { ...c, label: form.label }
          : c
      ));
      toast({ title: 'Категория обновлена' });
    } else {
      const id = form.label.toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]/g, '');
      setCategoryList(prev => [...prev, { id, label: form.label }]);
      toast({ title: 'Категория добавлена' });
    }
    setIsDialogOpen(false);
    resetForm();
  };

  const handleDelete = (id: string) => {
    if (getProductCount(id) > 0) {
      toast({ 
        title: 'Невозможно удалить', 
        description: 'В категории есть товары', 
        variant: 'destructive' 
      });
      return;
    }
    setCategoryList(prev => prev.filter(c => c.id !== id));
    toast({ title: 'Категория удалена' });
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
                  value={form.label} 
                  onChange={(e) => setForm({ ...form, label: e.target.value })} 
                  placeholder="Название категории"
                />
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setIsDialogOpen(false)}>Отмена</Button>
              <Button onClick={handleSave} disabled={!form.label}>
                {editingCategory ? 'Сохранить' : 'Добавить'}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {categoryList.map((category) => (
          <Card key={category.id} className="group">
            <CardContent className="p-6">
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="font-semibold text-lg">{category.label}</h3>
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
    </div>
  );
};

export default AdminCategories;
