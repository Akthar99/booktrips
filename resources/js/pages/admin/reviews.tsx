import StarRating from '@/components/booktrips/star-rating';
import Tabs from '@/components/booktrips/tabs';
import { withAppLayout } from '@/layouts/app-layout';
import { ADMIN_TABS } from '@/lib/admin-tabs';
import { formatDateTime } from '@/lib/booktrips';
import type { Paginated } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type ReviewRow = {
    id: number;
    rating: number;
    title: string | null;
    comment: string;
    created_at: string | null;
    package_title: string;
    package_id: number;
    user_name: string;
};

type ReviewsProps = { reviews: Paginated<ReviewRow> };

const AdminReviews: InertiaComponent<ReviewsProps> = ({ reviews }) => {
    return (
        <div className="mx-auto w-[min(1180px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">Super admin</h1>
            <Tabs items={ADMIN_TABS} />
            <h2 className="mb-3 text-2xl">Reviews</h2>
            <div className="overflow-x-auto rounded-2xl border border-line bg-white">
                <table className="w-full border-collapse text-left">
                    <thead>
                        <tr className="bg-cream-dark text-[11px] tracking-wide text-muted uppercase">
                            <th className="px-3.5 py-3 font-bold">Package</th>
                            <th className="px-3.5 py-3 font-bold">Guest</th>
                            <th className="px-3.5 py-3 font-bold">When</th>
                            <th className="px-3.5 py-3 font-bold">Score</th>
                            <th className="px-3.5 py-3 font-bold">Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        {reviews.data.map((review) => (
                            <tr key={review.id} className="border-t border-line">
                                <td className="px-3.5 py-3 text-sm">{review.package_title}</td>
                                <td className="px-3.5 py-3 text-sm">{review.user_name}</td>
                                <td className="px-3.5 py-3 text-sm">{formatDateTime(review.created_at)}</td>
                                <td className="px-3.5 py-3">
                                    <StarRating value={review.rating} readOnly size={14} />
                                </td>
                                <td className="px-3.5 py-3 text-sm">
                                    {review.title ? <strong>{review.title}. </strong> : null}
                                    {review.comment}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

AdminReviews.layout = withAppLayout;

export default AdminReviews;
