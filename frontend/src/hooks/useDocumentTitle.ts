import { useEffect } from 'react';

/**
 * Хук для установки title страницы
 * @param title - Заголовок страницы (будет добавлен к "Биофарм - ")
 * @param fullTitle - Если true, title используется полностью без префикса
 */
export const useDocumentTitle = (title: string, fullTitle = false) => {
  useEffect(() => {
    const previousTitle = document.title;
    document.title = fullTitle ? title : title ? `${title} - Биофарм` : 'Биофарм';
    
    return () => {
      document.title = previousTitle;
    };
  }, [title, fullTitle]);
};
