import { useState, useEffect, useCallback } from 'react';
import { User, authApi } from '@/data/users';

export function useAuth() {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const currentUser = authApi.getCurrentUser();
    setUser(currentUser);
    setIsLoading(false);
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    setIsLoading(true);
    try {
      const loggedInUser = await authApi.login(email, password);
      setUser(loggedInUser);
      return loggedInUser;
    } catch (error) {
      // Пробрасываем ошибку дальше
      throw error;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const register = useCallback(async (email: string, password: string, name: string, referredBy?: string) => {
    setIsLoading(true);
    try {
      const newUser = await authApi.register(email, password, name, referredBy);
      setUser(newUser);
      return newUser;
    } catch (error) {
      // Пробрасываем ошибку дальше
      throw error;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const logout = useCallback(async () => {
    await authApi.logout();
    setUser(null);
  }, []);

  const updateProfile = useCallback(async (data: Partial<User>) => {
    if (!user) return null;
    const updated = await authApi.updateProfile(user.id, data);
    if (updated) {
      setUser(updated);
    }
    return updated;
  }, [user]);

  const refreshUser = useCallback(async () => {
    const currentUser = authApi.getCurrentUser();
    if (!currentUser) return null;
    const refreshed = await authApi.refreshUser(currentUser.id);
    if (refreshed) {
      setUser(refreshed);
    }
    return refreshed;
  }, []); // Убираем зависимость от user, чтобы избежать бесконечного цикла

  return {
    user,
    isLoading,
    isAuthenticated: !!user,
    login,
    register,
    logout,
    updateProfile,
    refreshUser,
  };
}
