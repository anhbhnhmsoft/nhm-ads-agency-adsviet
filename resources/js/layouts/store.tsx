import { create } from 'zustand/react';


interface ISystemStore  {
    loading: boolean;
    setLoading: (loading: boolean) => void;
}

const systemStore = create<ISystemStore>((set) => ({
    loading: false,
    setLoading: (v) => set({ loading: v }),
}))

export default systemStore;

