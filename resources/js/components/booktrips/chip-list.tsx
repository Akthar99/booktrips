import { useState } from 'react';
import { X } from 'lucide-react';
import Button from '@/components/booktrips/button';
import { Field, Input } from '@/components/booktrips/field';

export default function ChipList({
    label,
    items,
    onChange,
    placeholder,
}: {
    label: string;
    items: string[];
    onChange: (items: string[]) => void;
    placeholder?: string;
}) {
    const [draft, setDraft] = useState('');
    const list = Array.isArray(items) ? items : [];

    function add() {
        const value = draft.trim();

        if (!value || list.includes(value)) {
            return;
        }

        onChange([...list, value]);
        setDraft('');
    }

    return (
        <Field label={label}>
            <div className="my-0 mb-2 flex flex-wrap gap-2">
                {list.map((item) => (
                    <button
                        type="button"
                        className="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-line bg-white px-2.5 py-1.5 text-[13px] font-semibold"
                        key={item}
                        onClick={() => onChange(list.filter((x) => x !== item))}
                    >
                        {item} <X size={12} />
                    </button>
                ))}
            </div>
            <div className="flex gap-2">
                <Input
                    value={draft}
                    placeholder={placeholder || 'Add item'}
                    onChange={(event) => setDraft(event.target.value)}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            add();
                        }
                    }}
                />
                <Button variant="outline" size="sm" onClick={add} className="shrink-0">
                    Add
                </Button>
            </div>
        </Field>
    );
}
