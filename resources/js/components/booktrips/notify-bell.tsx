import { useEffect, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { readAll } from '@/actions/App/Http/Controllers/NotificationController';
import type { NotificationItem, SharedProps } from '@/types/booktrips';

export default function NotifyBell() {
    const { auth, notifications } = usePage<SharedProps>().props;
    const [open, setOpen] = useState(false);
    const wrap = useRef<HTMLDivElement>(null);
    const user = auth.user;

    useEffect(() => {
        if (!user) {
            return undefined;
        }

        const timer = setInterval(() => {
            router.reload({ only: ['notifications'] });
        }, 60000);

        return () => clearInterval(timer);
    }, [user?.id]); // eslint-disable-line react-hooks/exhaustive-deps

    useEffect(() => {
        function onDoc(event: MouseEvent) {
            if (wrap.current && !wrap.current.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', onDoc);

        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    if (!user) {
        return null;
    }

    const unread = notifications?.unread ?? 0;
    const items = notifications?.items ?? [];

    function toggle() {
        setOpen((value) => !value);

        if (!open && unread > 0) {
            router.post(readAll.url(), {}, { preserveScroll: true, preserveState: true, only: ['notifications'] });
        }
    }

    function notificationLink(notification: NotificationItem): string {
        if (notification.link) {
            return notification.link;
        }

        if (!notification.booking_id) {
            return '#';
        }

        if (user.role === 'admin') {
            return '/admin/bookings';
        }

        return user.role === 'business'
            ? `/partners/bookings/${notification.booking_id}`
            : `/account/bookings/${notification.booking_id}`;
    }

    return (
        <div className="relative" ref={wrap}>
            <button
                type="button"
                className="relative grid cursor-pointer place-items-center border-0 bg-transparent p-2 text-brand-900"
                onClick={toggle}
                aria-label="Notifications"
            >
                <Bell size={20} />
                {unread ? (
                    <em className="absolute top-0.5 right-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-danger px-0.5 text-[10px] text-white not-italic">
                        {unread > 9 ? '9+' : unread}
                    </em>
                ) : null}
            </button>
            {open ? (
                <div className="absolute top-[calc(100%+8px)] right-0 z-90 max-h-[360px] w-70 overflow-auto rounded-[14px] border border-line bg-white p-2 shadow-card">
                    {items.length === 0 ? <p className="p-2.5 text-sm text-muted">No notifications</p> : null}
                    {items.slice(0, 8).map((notification) => (
                        <Link
                            key={notification.id}
                            href={notificationLink(notification)}
                            className="block rounded-[10px] px-3 py-2.5 hover:bg-brand-50"
                            onClick={() => setOpen(false)}
                        >
                            <strong className="block text-[13px] text-brand-900">{notification.title}</strong>
                            <span className="block text-xs font-medium text-muted">{notification.body}</span>
                        </Link>
                    ))}
                </div>
            ) : null}
        </div>
    );
}
