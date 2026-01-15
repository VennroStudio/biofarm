import { useState, useEffect } from 'react';
import { Plus, Search, Edit, Trash2, Eye } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { getBlogPosts, BlogPost } from '@/data/blogPosts';
import { useToast } from '@/hooks/use-toast';
import { api } from '@/lib/api';

const AdminBlog = () => {
  const { toast } = useToast();
  const [posts, setPosts] = useState<BlogPost[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getBlogPosts()
      .then(setPosts)
      .catch((error) => {
        console.error('Failed to load blog posts:', error);
        toast({ title: 'Ошибка загрузки статей', variant: 'destructive' });
      })
      .finally(() => setLoading(false));
  }, [toast]);
  const [search, setSearch] = useState('');
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingPost, setEditingPost] = useState<BlogPost | null>(null);
  
  const [form, setForm] = useState({
    title: '',
    excerpt: '',
    content: '',
    image: '',
    category: '',
    authorName: 'Редакция BioFarm',
    authorAvatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&q=80',
  });

  const filteredPosts = posts.filter(p => 
    p.title.toLowerCase().includes(search.toLowerCase())
  );

  if (loading) {
    return (
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Блог</h1>
          <p className="text-muted-foreground">Управление статьями</p>
        </div>
        <div className="text-center py-12">
          <p className="text-muted-foreground">Загрузка статей...</p>
        </div>
      </div>
    );
  }

  const resetForm = () => {
    setForm({
      title: '', excerpt: '', content: '', image: '', category: '', 
      authorName: 'Редакция BioFarm',
      authorAvatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&q=80',
    });
    setEditingPost(null);
  };

  const handleEdit = (post: BlogPost) => {
    setEditingPost(post);
    setForm({
      title: post.title,
      excerpt: post.excerpt,
      content: post.content,
      image: post.image,
      category: post.category,
      authorName: post.author.name,
      authorAvatar: post.author.avatar,
    });
    setIsDialogOpen(true);
  };

  const handleSave = async () => {
    const slug = form.title.toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]/g, '');
    
    try {
      if (editingPost) {
        const updatedPost = await api.blog.update(editingPost.id, {
          title: form.title,
          excerpt: form.excerpt,
          content: form.content,
          image: form.image || editingPost.image,
          category: form.category,
          categoryId: form.category,
          authorId: 1,
          readTime: Math.ceil(form.content.length / 1000),
          isPublished: true,
          slug: slug,
        });
        
        setPosts(prev => prev.map(p => 
          p.id === editingPost.id 
            ? { 
                id: updatedPost.id,
                slug: updatedPost.slug,
                title: updatedPost.title,
                excerpt: updatedPost.excerpt,
                content: updatedPost.content,
                image: updatedPost.image,
                category: updatedPost.category,
                author: { name: form.authorName, avatar: form.authorAvatar },
                date: new Date(updatedPost.date).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' }),
                readTime: `${updatedPost.readTime} мин`,
              }
            : p
        ));
        toast({ title: 'Статья обновлена' });
      } else {
        // For new posts, we would need a create endpoint
        // For now, just update local state
        const newPost: BlogPost = {
          id: Date.now(),
          slug,
          title: form.title,
          excerpt: form.excerpt,
          content: form.content,
          image: form.image || 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=800&q=80',
          category: form.category,
          author: { name: form.authorName, avatar: form.authorAvatar },
          date: new Date().toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' }),
          readTime: `${Math.ceil(form.content.length / 1000)} мин`,
        };
        setPosts(prev => [newPost, ...prev]);
        toast({ title: 'Статья добавлена' });
      }
      
      setIsDialogOpen(false);
      resetForm();
    } catch (error) {
      console.error('Failed to save blog post:', error);
      toast({ title: 'Ошибка сохранения статьи', variant: 'destructive' });
    }
  };

  const handleDelete = async (id: number) => {
    try {
      await api.blog.delete(id);
      setPosts(prev => prev.filter(p => p.id !== id));
      toast({ title: 'Статья удалена' });
    } catch (error) {
      console.error('Failed to delete blog post:', error);
      toast({ title: 'Ошибка удаления статьи', variant: 'destructive' });
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold">Блог</h1>
          <p className="text-muted-foreground">Управление статьями блога</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if (!open) resetForm(); }}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              Написать статью
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingPost ? 'Редактировать статью' : 'Новая статья'}</DialogTitle>
            </DialogHeader>
            <div className="grid gap-4 py-4">
              <div className="space-y-2">
                <Label>Заголовок *</Label>
                <Input value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Категория *</Label>
                  <Input value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })} placeholder="Здоровье" />
                </div>
                <div className="space-y-2">
                  <Label>Имя автора</Label>
                  <Input value={form.authorName} onChange={(e) => setForm({ ...form, authorName: e.target.value })} />
                </div>
              </div>
              <div className="space-y-2">
                <Label>URL изображения</Label>
                <Input value={form.image} onChange={(e) => setForm({ ...form, image: e.target.value })} placeholder="https://..." />
                <p className="text-xs text-muted-foreground">
                  Загрузка файлов будет доступна после подключения серверного хранилища
                </p>
              </div>
              <div className="space-y-2">
                <Label>Краткое описание *</Label>
                <Input value={form.excerpt} onChange={(e) => setForm({ ...form, excerpt: e.target.value })} />
              </div>
              <div className="space-y-2">
                <Label>Содержание *</Label>
                <RichTextEditor 
                  content={form.content} 
                  onChange={(content) => setForm({ ...form, content: content })}
                  className="min-h-[300px]"
                />
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setIsDialogOpen(false)}>Отмена</Button>
              <Button onClick={handleSave} disabled={!form.title || !form.excerpt || !form.content}>
                {editingPost ? 'Сохранить' : 'Опубликовать'}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center gap-4">
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input 
                placeholder="Поиск статей..." 
                value={search} 
                onChange={(e) => setSearch(e.target.value)}
                className="pl-10"
              />
            </div>
            <Badge variant="secondary">{filteredPosts.length} статей</Badge>
          </div>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-16">Фото</TableHead>
                  <TableHead>Заголовок</TableHead>
                  <TableHead>Категория</TableHead>
                  <TableHead>Автор</TableHead>
                  <TableHead>Дата</TableHead>
                  <TableHead className="text-right">Действия</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredPosts.map((post) => (
                  <TableRow key={post.id}>
                    <TableCell>
                      <img src={post.image} alt={post.title} className="w-12 h-12 object-cover rounded" />
                    </TableCell>
                    <TableCell>
                      <p className="font-medium line-clamp-1">{post.title}</p>
                      <p className="text-sm text-muted-foreground line-clamp-1">{post.excerpt}</p>
                    </TableCell>
                    <TableCell>
                      <Badge variant="outline">{post.category}</Badge>
                    </TableCell>
                    <TableCell>{post.author.name}</TableCell>
                    <TableCell>{post.date}</TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button variant="ghost" size="icon" asChild>
                          <a href={`/blog/${post.slug}`} target="_blank" rel="noopener noreferrer">
                            <Eye className="h-4 w-4" />
                          </a>
                        </Button>
                        <Button variant="ghost" size="icon" onClick={() => handleEdit(post)}>
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button variant="ghost" size="icon" className="text-destructive" onClick={() => handleDelete(post.id)}>
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default AdminBlog;
