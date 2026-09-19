import { useEffect, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import Logo from '@/components/booktrips/logo';
import NotifyBell from '@/components/booktrips/notify-bell';
import type { SharedProps } from '@/types/booktrips';

export default function Header() {
    const { auth, url } = usePage<SharedProps>().props;
    const [open, setOpen] = useState(false);
    const wrap = useRef<HTMLDivElement>(null);
    const user = auth.user;
    const approved = Boolean(user?.business?.approved);
    const isPartner = user?.role === 'business' && approved;
    const isAdmin = user?.role === 'admin';
    const onHome = url === '/';

    useEffect(() => {
        setOpen(false);
    }, [url]);

    useEffect(() => {
        function onDoc(event: MouseEvent) {
            if (wrap.current && !wrap.current.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', onDoc);

        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    function logout() {
        setOpen(false);
        router.post('/logout');
    }

    const navLink =
        'font-semibold text-muted transition hover:text-brand-900 text-sm';

    return (
        <header
            className={
                'bg-cream/94 sticky top-0 z-40 flex min-h-18 flex-col justify-center backdrop-blur-lg ' +
                (onHome
                    ? 'border-b border-transparent'
                    : 'border-line border-b')
            }
        >
            <div className="mx-auto flex min-h-18 w-[min(1180px,calc(100%-2rem))] items-center gap-7">
                <Link href="/">
                    <Logo />
                </Link>
                <nav className="text-muted hidden items-center gap-4.5 text-sm font-semibold lg:flex">
                    {!isPartner ? (
                        <Link href="/search" className={navLink}>
                            Explore
                        </Link>
                    ) : null}
                    <Link href="/map" className={navLink}>
                        Map
                    </Link>
                    {!isPartner && !isAdmin ? (
                        <Link href="/partners" className={navLink}>
                            For partners
                        </Link>
                    ) : null}
                </nav>
                <div
                    className="relative ml-auto flex items-center gap-2.5"
                    ref={wrap}
                >
                    <NotifyBell />
                    {isPartner ? (
                        <Link
                            href="/partners/dashboard"
                            className="bg-brand-800 hover:bg-brand-900 hidden rounded-full px-4 py-2.5 text-sm font-bold text-white transition sm:inline-flex"
                        >
                            Dashboard
                        </Link>
                    ) : user ? (
                        <Link
                            href="/account/bookings"
                            className="border-line text-brand-900 hover:border-brand-700 hidden rounded-full border bg-white px-4 py-2.5 text-sm font-bold transition sm:inline-flex"
                        >
                            My trips
                        </Link>
                    ) : (
                        <Link
                            href="/login"
                            className="text-brand-900 hover:bg-brand-50 hidden rounded-full px-4 py-2.5 text-sm font-bold transition sm:inline-flex"
                        >
                            Log in
                        </Link>
                    )}
                    <button
                        type="button"
                        className="text-brand-900 grid cursor-pointer place-items-center border-0 bg-transparent p-2"
                        onClick={() => setOpen((value) => !value)}
                        aria-label={open ? 'Close menu' : 'Open menu'}
                        aria-expanded={open}
                    >
                        {open ? <X size={22} /> : <Menu size={22} />}
                    </button>
                    {open ? (
                        <div className="border-line shadow-card absolute top-[calc(100%+8px)] right-0 z-90 w-55 rounded-[14px] border bg-white p-2">
                            {[
                                isPartner
                                    ? [
                                          ['Dashboard', '/partners/dashboard'],
                                          [
                                              'Reservations',
                                              '/partners/bookings',
                                          ],
                                          ['Finance', '/partners/payments'],
                                          ['Map', '/map'],
                                          ['My account', '/account'],
                                          ['Help & support', '/support'],
                                      ]
                                    : [
                                          ['Explore', '/search'],
                                          ['Map', '/map'],
                                          ['For partners', '/partners'],
                                          ...(user
                                              ? [
                                                    ['My account', '/account'],
                                                    [
                                                        'My bookings',
                                                        '/account/bookings',
                                                    ],
                                                    ...(user.role ===
                                                        'business' && !approved
                                                        ? [
                                                              [
                                                                  'Application',
                                                                  '/partners/pending',
                                                              ],
                                                          ]
                                                        : []),
                                                    ...(isAdmin
                                                        ? [['Admin', '/admin']]
                                                        : []),
                                                    [
                                                        'Help & support',
                                                        '/support',
                                                    ],
                                                ]
                                              : [
                                                    ['Log in', '/login'],
                                                    ['Sign up', '/register'],
                                                ]),
                                      ],
                            ]
                                .flat()
                                .map(([label, href]) => (
                                    <Link
                                        key={href}
                                        href={href}
                                        className="text-brand-900 hover:bg-brand-50 block rounded-[10px] px-3 py-2.5 text-sm font-bold"
                                    >
                                        {label}
                                    </Link>
                                ))}
                            {user ? (
                                <button
                                    type="button"
                                    className="text-brand-900 hover:bg-brand-50 block w-full cursor-pointer rounded-[10px] border-0 bg-transparent px-3 py-2.5 text-left text-sm font-bold"
                                    onClick={logout}
                                >
                                    Log out
                                </button>
                            ) : null}
                        </div>
                    ) : null}
                </div>
            </div>
        </header>
    );
}
