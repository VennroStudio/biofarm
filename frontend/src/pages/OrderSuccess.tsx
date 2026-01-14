import { Link, useLocation } from 'react-router-dom';
import { motion } from 'framer-motion';
import { CheckCircle, Package, Home, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';

const OrderSuccess = () => {
  const location = useLocation();
  const orderId = location.state?.orderId || 'ORD-DEMO';

  return (
    <div className="min-h-screen flex flex-col">
      <Header />
      
      <main className="flex-1 flex items-center justify-center py-12 bg-secondary/30">
        <div className="container px-4 max-w-lg">
          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ duration: 0.5 }}
          >
            <Card className="border-0 shadow-premium-lg text-center">
              <CardContent className="pt-8 pb-8 px-6">
                <motion.div
                  initial={{ scale: 0 }}
                  animate={{ scale: 1 }}
                  transition={{ delay: 0.2, type: 'spring', stiffness: 200 }}
                  className="mb-6"
                >
                  <div className="w-20 h-20 mx-auto rounded-full bg-green-100 flex items-center justify-center">
                    <CheckCircle className="h-10 w-10 text-green-600" />
                  </div>
                </motion.div>
                
                <h1 className="text-2xl font-bold mb-2">Заказ оформлен!</h1>
                <p className="text-muted-foreground mb-6">
                  Спасибо за ваш заказ. Мы уже начали его обработку.
                </p>
                
                <div className="bg-muted/50 rounded-lg p-4 mb-6">
                  <p className="text-sm text-muted-foreground">Номер заказа</p>
                  <p className="text-xl font-bold text-primary">{orderId}</p>
                </div>
                
                <div className="space-y-3 text-left mb-6">
                  <div className="flex items-start gap-3 p-3 bg-background rounded-lg">
                    <Package className="h-5 w-5 text-primary mt-0.5" />
                    <div>
                      <p className="font-medium">Что дальше?</p>
                      <p className="text-sm text-muted-foreground">
                        Менеджер свяжется с вами для подтверждения заказа и уточнения деталей доставки.
                      </p>
                    </div>
                  </div>
                </div>
                
                <div className="flex flex-col sm:flex-row gap-3">
                  <Button asChild className="flex-1">
                    <Link to="/profile">
                      <User className="h-4 w-4 mr-2" />
                      Мои заказы
                    </Link>
                  </Button>
                  <Button asChild variant="outline" className="flex-1">
                    <Link to="/">
                      <Home className="h-4 w-4 mr-2" />
                      На главную
                    </Link>
                  </Button>
                </div>
              </CardContent>
            </Card>
          </motion.div>
        </div>
      </main>
      
      <Footer />
    </div>
  );
};

export default OrderSuccess;
