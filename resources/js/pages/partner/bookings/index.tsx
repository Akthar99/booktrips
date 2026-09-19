import { updateStatus as partnerBookingStatus } from '@/actions/App/Http/Controllers/Partner/PartnerBookingController';
import BookingsManager from '@/components/booktrips/bookings-manager';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import type { BookingData, Paginated } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type PartnerBookingsProps = {
    bookings: Paginated<BookingData>;
    filters: { q: string; status: string; date: string };
    counts: { requested: number; confirmed: number };
};

const PartnerBookings: InertiaComponent<PartnerBookingsProps> = ({
    bookings,
    filters,
    counts,
}) => {
    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Reservations</h1>
            <p className="text-muted">
                Confirm or reject requests. Mark a stay finished when the guest
                has gone — that adds 10% to your bill.
            </p>
            <Tabs
                items={[
                    {
                        label: 'Overview',
                        href: '/partners/dashboard',
                        exact: true,
                    },
                    { label: 'Reservations', href: '/partners/bookings' },
                    { label: 'Analytics', href: '/partners/analytics' },
                    { label: 'Finance', href: '/partners/payments' },
                ]}
            />
            <BookingsManager
                bookings={bookings}
                filters={filters}
                counts={counts}
                mode="partner"
                baseUrl="/partners/bookings"
                statusUrlFor={(bookingId) =>
                    partnerBookingStatus.url(bookingId)
                }
            />
        </div>
    );
};

PartnerBookings.layout = withAppLayout;

export default PartnerBookings;
