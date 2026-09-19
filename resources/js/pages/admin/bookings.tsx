import { updateStatus as adminBookingStatus } from '@/actions/App/Http/Controllers/Admin/AdminBookingController';
import BookingsManager from '@/components/booktrips/bookings-manager';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { ADMIN_TABS } from '@/lib/admin-tabs';
import type { BookingData, Paginated } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type AdminBookingsProps = {
    bookings: Paginated<BookingData>;
    filters: { q: string; status: string; date: string };
    counts: { requested: number; confirmed: number };
};

const AdminBookings: InertiaComponent<AdminBookingsProps> = ({
    bookings,
    filters,
    counts,
}) => {
    return (
        <div className="mx-auto w-[min(1280px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Super admin</h1>
            <Tabs items={ADMIN_TABS} />
            <h2 className="mb-3 text-2xl">Reservations</h2>
            <BookingsManager
                bookings={bookings}
                filters={filters}
                counts={counts}
                mode="admin"
                baseUrl="/admin/bookings"
                statusUrlFor={(bookingId) => adminBookingStatus.url(bookingId)}
            />
        </div>
    );
};

AdminBookings.layout = withAppLayout;

export default AdminBookings;
