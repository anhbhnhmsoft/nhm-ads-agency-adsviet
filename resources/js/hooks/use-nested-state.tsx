import { useState, useCallback } from 'react';

export type NestedPartial<T> = {
    [K in keyof T]?: T[K] extends Record<string, any> ? NestedPartial<T[K]> : T[K];
};

function deepMerge<T>(target: T, source: NestedPartial<T>): T {
    if (typeof target !== 'object' || target === null || typeof source !== 'object' || source === null) {
        return source as T;
    }
    const result = { ...target };

    for (const key in source) {
        if (source[key] !== undefined) {
            if (typeof source[key] === 'object' && source[key] !== null && !Array.isArray(source[key])) {
                result[key] = deepMerge((target as any)[key], source[key]) as any;
            } else {
                result[key] = source[key] as any;
            }
        }
    }

    return result as T;
}
/**
 *  dùng để set các state có kiểu nested
 */
export default function useNestedState<T extends Record<string, any>>(initialState: T) {
    const [state, setState] = useState<T>(initialState);

    const updateState = useCallback((newState: NestedPartial<T>) => {
        setState(prevState => ({...prevState, ...deepMerge(prevState, newState)}));
    }, []);

    return [state, updateState] as const;
}


