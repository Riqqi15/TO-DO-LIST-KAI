import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

export function getContrastTextColor(hexcolor) {
    if (!hexcolor) return '#ffffff';
    hexcolor = hexcolor.replace('#', '');
    let r = parseInt(hexcolor.substr(0, 2), 16);
    let g = parseInt(hexcolor.substr(2, 2), 16);
    let b = parseInt(hexcolor.substr(4, 2), 16);
    let yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
    return (yiq >= 128) ? '#0f172a' : '#ffffff';
}

export function getTextColorForBackground(hexcolor) {
    return getContrastTextColor(hexcolor) === '#0f172a' ? 'text-slate-900' : 'text-white';
}

export function getCategoryColor(categoryOrName, fallbackHex = null) {
    let name = '';
    let hex = fallbackHex;
    
    if (typeof categoryOrName === 'string') {
        name = categoryOrName.toLowerCase();
    } else if (categoryOrName) {
        name = categoryOrName.name?.toLowerCase() || '';
        hex = categoryOrName.color || fallbackHex;
    }

    if (!name && !hex) return { class: 'bg-secondary text-secondary-foreground hover:bg-secondary/80 border-transparent', style: {} };
    
    if (name.includes('spk') || name.includes('laporan')) {
        return { class: 'bg-blue-800 text-white hover:bg-blue-800/90 border-transparent', style: {} }; // Navy
    }
    
    if (name === 'g63' || name === 'g61') {
        return { class: 'bg-amber-700 text-white hover:bg-amber-700/90 border-transparent', style: {} }; // Brown
    }

    if (hex) {
        const textColor = getTextColorForBackground(hex);
        const borderClass = textColor === 'text-slate-900' ? 'border-black/20 dark:border-white/20' : 'border-transparent';
        return { class: `${textColor} hover:opacity-90 ${borderClass} font-medium border`, style: { backgroundColor: hex } };
    }
    
    return { class: 'bg-secondary text-secondary-foreground hover:bg-secondary/80 border-transparent', style: {} };
}
