import {
    BadgeCheck,
    Bitcoin,
    Bot,
    ChevronLeft,
    ChevronRight,
    ChevronDown,
    createIcons,
    Github,
    Lightbulb,
    Loader2,
    LogIn,
    LogOut,
    Moon,
    Scroll,
    Send,
    Shuffle,
    SlidersHorizontal,
    Sun,
    User,
    Zap,
    ArrowUp,
    Scissors,
    Laptop,
    Lock,
    Unlock,
    ExternalLink,
    X,
} from 'lucide';

export const icons = {
    ChevronLeft,
    ChevronRight,
    ChevronDown,
    Bitcoin,
    Bot,
    Loader2,
    Lightbulb,
    Scroll,
    Sun,
    Moon,
    Github,
    SlidersHorizontal,
    Zap,
    Shuffle,
    User,
    Send,
    BadgeCheck,
    ArrowUp,
    Scissors,
    Laptop,
    Lock,
    Unlock,
    LogIn,
    LogOut,
    ExternalLink,
    X,
};

export const initIcons = () => {
    createIcons({ icons });
};

export const refreshIcons = () => {
    requestAnimationFrame(() => createIcons({ icons }));
};
