import { useEffect, useState } from 'react';

export function useLoadOnMount(load: () => Promise<void> | void) {
  const [initialLoad] = useState(() => load);

  useEffect(() => {
    let cancelled = false;

    queueMicrotask(() => {
      if (!cancelled) {
        Promise.resolve(initialLoad()).catch((error: unknown) => {
          console.error('Failed to load admin data', error);
        });
      }
    });

    return () => {
      cancelled = true;
    };
  }, [initialLoad]);
}
