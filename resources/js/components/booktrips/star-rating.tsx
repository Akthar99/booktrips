import { Star } from 'lucide-react';

export default function StarRating({
    value = 0,
    onChange,
    readOnly = false,
    size = 17,
}: {
    value?: number;
    onChange?: (rating: number) => void;
    readOnly?: boolean;
    size?: number;
}) {
    const score = Number(value || 0);

    return (
        <div className="inline-flex items-center gap-0.5 text-gold" aria-label={`${score} out of 5 stars`}>
            {[1, 2, 3, 4, 5].map((star) => (
                <button
                    key={star}
                    type="button"
                    className={
                        'inline-flex items-center justify-center border-0 bg-transparent p-0 ' +
                        (star <= score ? 'text-[#d59b22]' : 'text-[#d6d2c8]') +
                        (readOnly ? ' cursor-default' : ' cursor-pointer hover:text-[#b97905]')
                    }
                    onClick={() => !readOnly && onChange?.(star)}
                    disabled={readOnly}
                    aria-label={`${star} star${star === 1 ? '' : 's'}`}
                >
                    <Star size={size} fill={star <= score ? 'currentColor' : 'none'} />
                </button>
            ))}
            {!readOnly ? <span className="ml-1.5 text-[13px] font-bold text-muted">{score}/5</span> : null}
        </div>
    );
}
