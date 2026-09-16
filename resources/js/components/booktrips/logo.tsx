export default function Logo({ light = false }: { light?: boolean }) {
    return (
        <span
            className={
                'flex items-center gap-2.5 text-xl font-extrabold tracking-[-0.04em] ' +
                (light ? 'text-white' : 'text-brand-900')
            }
        >
            <svg viewBox="0 0 36 36" fill="none" aria-hidden="true" className="h-[34px] w-[34px]">
                <rect width="36" height="36" rx="10" fill="#3E9B6C" />
                <path d="M8 24c5-11 15-11 20 0" stroke="#F7F4EE" strokeWidth="2.4" strokeLinecap="round" />
                <circle cx="18" cy="12" r="3.4" fill="#D8F3DC" />
            </svg>
            booktrips
        </span>
    );
}
