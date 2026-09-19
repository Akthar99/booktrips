import { useEffect, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { index as searchRoute } from '@/actions/App/Http/Controllers/PackageController';
import DateInput from '@/components/booktrips/date-input';
import { Select } from '@/components/booktrips/field';

export default function SearchBar({
    destinations,
    initial,
}: {
    destinations: Array<{ name: string }>;
    initial: {
        location?: string;
        check_in?: string;
        check_out?: string;
        guests?: string;
    };
}) {
    const { url } = usePage();
    const [form, setForm] = useState({
        location: initial.location || '',
        check_in: initial.check_in || '',
        check_out: initial.check_out || '',
        guests: initial.guests || '2',
    });

    useEffect(() => {
        setForm({
            location: initial.location || '',
            check_in: initial.check_in || '',
            check_out: initial.check_out || '',
            guests: initial.guests || '2',
        });
    }, [initial.location, initial.check_in, initial.check_out, initial.guests]);

    function submit(event: React.FormEvent) {
        event.preventDefault();

        const query = new URLSearchParams(
            url.includes('?') ? url.split('?')[1] : '',
        );

        (['location', 'check_in', 'check_out', 'guests'] as const).forEach(
            (key) => {
                if (form[key]) {
                    query.set(key, form[key]);
                } else {
                    query.delete(key);
                }
            },
        );

        query.delete('page');

        router.get(
            searchRoute.url({ query: Object.fromEntries(query.entries()) }),
            {},
            { preserveState: true },
        );
    }

    return (
        <form
            className="shadow-card grid overflow-visible rounded-[20px] bg-white md:grid-cols-[1.5fr_1.6fr_0.8fr_auto]"
            onSubmit={submit}
        >
            <label className="border-line flex min-w-0 flex-col gap-1 border-b px-4.5 py-3.5 md:border-r md:border-b-0">
                <span className="text-muted text-[11px] font-bold tracking-wide uppercase">
                    Where to
                </span>
                <input
                    list="bt-destinations"
                    placeholder="Town or area"
                    className="w-full border-0 bg-transparent text-[15px] font-semibold outline-none"
                    value={form.location}
                    onChange={(event) =>
                        setForm({ ...form, location: event.target.value })
                    }
                />
                <datalist id="bt-destinations">
                    {destinations.map((destination) => (
                        <option
                            key={destination.name}
                            value={destination.name}
                        />
                    ))}
                </datalist>
            </label>
            <div className="border-line grid min-w-0 grid-cols-2 border-b md:border-r md:border-b-0">
                <DateInput
                    bare
                    className="border-line min-w-0 border-r px-4.5 py-3.5 [&>span]:text-[11px]"
                    label="Start"
                    value={form.check_in}
                    onChange={(check_in) => setForm({ ...form, check_in })}
                />
                <DateInput
                    bare
                    className="min-w-0 px-4.5 py-3.5"
                    label="End"
                    value={form.check_out}
                    min={form.check_in || undefined}
                    onChange={(check_out) => setForm({ ...form, check_out })}
                />
            </div>
            <label className="border-line flex min-w-0 flex-col gap-1 border-b px-4.5 py-3.5 md:border-r md:border-b-0">
                <span className="text-muted text-[11px] font-bold tracking-wide uppercase">
                    Guests
                </span>
                <Select
                    className="border-0 bg-transparent p-0 text-[15px] font-semibold focus:ring-0"
                    value={form.guests}
                    onChange={(event) =>
                        setForm({ ...form, guests: event.target.value })
                    }
                >
                    {[1, 2, 3, 4, 5, 6, 8, 10].map((n) => (
                        <option key={n} value={n}>
                            {n} {n === 1 ? 'guest' : 'guests'}
                        </option>
                    ))}
                </Select>
            </label>
            <button
                type="submit"
                className="bg-brand-800 hover:bg-brand-900 m-2.5 cursor-pointer rounded-[14px] px-5.5 py-3 text-sm font-bold text-white transition md:px-5.5"
            >
                Search
            </button>
        </form>
    );
}
