/**
 * Shared navigation for the admin console — keep in sync with routes/web.php.
 */
export const ADMIN_TABS = [
    { label: 'Overview', href: '/admin', exact: true },
    { label: 'Users', href: '/admin/users' },
    { label: 'Partners', href: '/admin/partners' },
    { label: 'Listings', href: '/admin/listings' },
    { label: 'Bookings', href: '/admin/bookings' },
    { label: 'Finance', href: '/admin/payments' },
    { label: 'Reports', href: '/admin/disputes' },
    { label: 'Support', href: '/admin/support' },
    { label: 'Reviews', href: '/admin/reviews' },
];
