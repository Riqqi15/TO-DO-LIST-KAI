import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

export function getCategoryColor(categoryName) {
    if (!categoryName) return 'bg-secondary text-secondary-foreground hover:bg-secondary/80 border-transparent';
    
    const name = categoryName.toLowerCase();
    
    if (name.includes('spk') || name.includes('laporan')) {
        return 'bg-slate-800 text-white hover:bg-slate-800/90 border-transparent'; // Navy
    }
    
    if (name === 'g63' || name === 'g61') {
        return 'bg-amber-800 text-white hover:bg-amber-800/90 border-transparent'; // Brown
    }
    
    return 'bg-secondary text-secondary-foreground hover:bg-secondary/80 border-transparent'; // Gray (Default)
}
