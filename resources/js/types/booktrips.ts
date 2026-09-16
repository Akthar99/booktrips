export type Role = 'user' | 'business' | 'admin';

export type BookingStatusValue =
    | 'requested'
    | 'confirmed'
    | 'rejected'
    | 'completed'
    | 'cancelled';

export type InvoiceStatusValue = 'open' | 'submitted' | 'paid';

export type ReceiptStatusValue = 'pending' | 'confirmed' | 'rejected';

export type PriceTypeValue = 'per_package' | 'per_person' | 'per_night';

export type DiscountTypeValue = 'none' | 'percentage' | 'fixed';

export interface BusinessSummary {
    id: number;
    name: string;
    approved: boolean;
    type: string;
    city: string;
    cover_image: string | null;
}

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    phone: string;
    role: Role;
    email_verified: boolean;
    pending_email: string | null;
    business: BusinessSummary | null;
}

export interface NotificationItem {
    id: string;
    type: string;
    title: string;
    body: string;
    booking_id: number | null;
    link: string | null;
    read: boolean;
    created_at: string | null;
}

export interface HostSummary {
    id: number;
    name: string;
    type: string;
    city: string;
    cover_image: string | null;
}

export interface PackageCardData {
    id: number;
    title: string;
    slug: string;
    category: string;
    highlight: string | null;
    location: string;
    district: string | null;
    images: string[];
    price_lkr: number;
    price_type: PriceTypeValue;
    duration_days: number;
    duration_nights: number;
    rating: number;
    review_count: number;
    featured: boolean;
    active: boolean;
    base_price_lkr: number;
    display_price_lkr: number;
    discount_lkr: number;
    discount_active: boolean;
    discount_type: DiscountTypeValue;
    discount_value: number;
    discount_label: string;
    host: HostSummary | null;
}

export interface ItineraryDay {
    day: number;
    title: string;
    description: string;
}

export interface PackageDetailData extends PackageCardData {
    business_id: number;
    description: string;
    address: string | null;
    lat: number | null;
    lng: number | null;
    schedule_type: 'always' | 'range';
    schedule_start: string | null;
    schedule_end: string | null;
    weekdays: number[];
    min_guests: number;
    max_guests: number;
    included: string[];
    excluded: string[];
    itinerary: ItineraryDay[];
    amenities: string[];
    meeting_point: string | null;
    cancellation_policy: string | null;
}

export interface ReviewData {
    id: number;
    rating: number;
    title: string | null;
    comment: string;
    user_name?: string;
    created_at: string | null;
}

export interface BookingData {
    id: number;
    booking_code: string;
    check_in: string;
    check_out: string;
    guests: number;
    base_total_lkr: number;
    discount_lkr: number;
    discount_applied: boolean;
    discount_label: string;
    total_lkr: number;
    status: BookingStatusValue;
    payment_method: string;
    guest_name: string;
    guest_phone: string;
    notes: string | null;
    commission_added: boolean;
    commission_lkr: number;
    escalated: boolean;
    created_at: string | null;
    package: {
        id: number;
        title: string;
        slug: string;
        location: string;
        images: string[];
        category: string;
        business_id: number;
    } | null;
    host: {
        id: number;
        name: string;
        phone: string | null;
        email: string | null;
    } | null;
    guest_email?: string;
    contact_hidden?: boolean;
    review?: ReviewData | null;
}

export interface ReceiptData {
    id: number;
    note: string | null;
    original_name: string | null;
    status: ReceiptStatusValue;
    created_at: string | null;
    reviewed_at: string | null;
    file_url: string;
}

export interface InvoiceData {
    id: number;
    period: string;
    period_label: string;
    due_date: string;
    amount_lkr: number;
    status: InvoiceStatusValue;
    lines: Array<{
        booking_id: number;
        booking_code: string;
        amount_lkr: number;
        total_lkr: number;
    }>;
    paid_at: string | null;
    receipts: ReceiptData[];
    business_name?: string;
}

export interface BankDetails {
    bank_name: string;
    account_name: string;
    account_number: string;
    branch: string;
    swift: string;
    note: string;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface SharedProps {
    name: string;
    auth: {
        user: AuthUser | null;
    };
    flash: {
        success?: string | null;
        error?: string | null;
        status?: string | null;
    };
    notifications: {
        items: NotificationItem[];
        unread: number;
    };
    [key: string]: unknown;
}
