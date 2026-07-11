import { useEffect, useState } from 'react';

export function useLoadOnMount(load: () => Promise<void> | void) {
  const [initialLoad] = useState(() => load);

  useEffect(() => {
    let cancelled = false;

    queueMicrotask(() => {
      if (!cancelled) {
        void initialLoad();
      }
    });

    return () => {
      cancelled = true;
    };
  }, [initialLoad]);
}
