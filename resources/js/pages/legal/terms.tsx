import { Link } from '@inertiajs/react';
import LegalShell, { LegalSection } from '@/components/booktrips/legal-shell';
import { withAppLayout } from '@/layouts/app-layout';
import type { InertiaComponent } from '@/types/inertia';

const Terms: InertiaComponent<Record<string, never>> = () => {
    return (
        <LegalShell
            title="Terms of Service"
            updated="19 September 2026"
            intro="These Terms of Service (“Terms”) govern your use of BookTrips (booktrips.lk), operated by Caymass Holidays Pvt Ltd. By creating an account, requesting a booking, listing a package or otherwise using the Service, you agree to these Terms and to our Privacy Policy. If you do not agree, please do not use the Service."
        >
            <LegalSection heading="1. What BookTrips is">
                <p>
                    BookTrips is a marketplace that connects travellers with
                    hotels, villas, camps, guides and activity operators across
                    Sri Lanka. Hosts create the listings and deliver the
                    experiences. The contract for a trip is between you and the
                    host; we are not the operator and are not a party to that
                    contract. We provide the platform, the booking tools and the
                    support that keeps both sides honest.
                </p>
            </LegalSection>

            <LegalSection heading="2. Accounts">
                <ul className="list-disc space-y-1 pl-5">
                    <li>
                        You must provide accurate, current information and keep
                        it up to date.
                    </li>
                    <li>
                        You must verify your email address (and, for bookings,
                        your mobile number) before those features unlock.
                    </li>
                    <li>
                        You are responsible for your password and for everything
                        done through your account. Tell us immediately if you
                        suspect unauthorised use.
                    </li>
                    <li>
                        You must be at least 18 years old to complete a booking.
                    </li>
                    <li>
                        Partner accounts are reviewed and approved by our team
                        before they can publish packages.
                    </li>
                </ul>
            </LegalSection>

            <LegalSection heading="3. Bookings and payment">
                <ul className="list-disc space-y-1 pl-5">
                    <li>
                        A booking starts as a <strong>request</strong>. The host
                        reviews it and may confirm or decline it. You will see
                        the decision in your account and, where enabled, receive
                        an email and SMS.
                    </li>
                    <li>
                        Prices are shown in Sri Lankan Rupees (LKR) and are set
                        by the host. The total shown when you request a booking
                        is what you pay.
                    </li>
                    <li>
                        <strong>You pay the host when you arrive</strong> — cash
                        or whatever payment method the host accepts on site.
                        BookTrips does not collect the booking amount from
                        travellers.
                    </li>
                    <li>
                        Your booking code is the reference the host needs. Keep
                        it and your confirmation safe.
                    </li>
                    <li>
                        Availability can change; hosts may decline requests. We
                        may also cancel or refuse bookings that look fraudulent,
                        abusive or contrary to these Terms.
                    </li>
                </ul>
            </LegalSection>

            <LegalSection heading="4. Cancellations">
                <ul className="list-disc space-y-1 pl-5">
                    <li>
                        Travellers can cancel a booking from their account while
                        it is in a cancellable state (a pending request or an
                        upcoming confirmed stay). The host is notified
                        immediately.
                    </li>
                    <li>
                        Each host sets their own cancellation policy, which is
                        shown on the package page and applies to the booking.
                    </li>
                    <li>
                        If a host cancels a confirmed booking, you will be
                        notified and the booking will be marked as cancelled.
                        Any amounts already paid directly to the host are
                        settled with the host.
                    </li>
                </ul>
            </LegalSection>

            <LegalSection heading="5. Partner terms">
                <p>
                    If you list packages on BookTrips, you also agree to the
                    following:
                </p>
                <ul className="list-disc space-y-1 pl-5">
                    <li>
                        List only experiences you are legally able to provide,
                        with any licences, permits and insurance required by Sri
                        Lankan law.
                    </li>
                    <li>
                        Describe your packages accurately — what is included,
                        what is not, where and when things happen — and keep
                        availability and pricing up to date.
                    </li>
                    <li>
                        Upload only photos you own or have the right to use. We
                        apply a BookTrips watermark to uploaded photos and may
                        use them to promote your listing and the platform.
                    </li>
                    <li>
                        Honour confirmed bookings at the confirmed price,
                        respond to requests promptly, and treat travellers
                        fairly.
                    </li>
                    <li>
                        Commission and fees are described in the agreement you
                        accept as a partner. Repeated cancellations, no-shows,
                        misrepresentation or off-platform fee circumvention can
                        lead to suspension and penalties (see section 7).
                    </li>
                </ul>
            </LegalSection>

            <LegalSection heading="6. Reviews and content">
                <ul className="list-disc space-y-1 pl-5">
                    <li>
                        Reviews may only be left by travellers who completed a
                        booking for that package.
                    </li>
                    <li>
                        Keep reviews honest and civil; we may remove content
                        that is fake, abusive or unlawful.
                    </li>
                    <li>
                        By posting content you grant us a non-exclusive,
                        worldwide licence to host, display and promote it in
                        connection with the Service. You keep ownership of your
                        content.
                    </li>
                </ul>
            </LegalSection>

            <LegalSection heading="7. Reports and disputes">
                <p>
                    If something goes wrong on a confirmed or completed booking,
                    either side can open a report against the other from the
                    booking page. The other party gets a response window
                    (currently 48 hours) and both sides can add evidence. Our
                    team reviews the report and issues a decision, which may
                    include warnings, strikes, suspension of the account or a
                    penalty for the party at fault. Three strikes can suspend an
                    account. Decisions are made in good faith based on the
                    evidence available and are final within the platform.
                </p>
            </LegalSection>

            <LegalSection heading="8. Prohibited conduct">
                <p>When using the Service you must not:</p>
                <ul className="list-disc space-y-1 pl-5">
                    <li>break any law, or help anyone else break one;</li>
                    <li>
                        post false, misleading or fraudulent information, or
                        impersonate anyone;
                    </li>
                    <li>
                        attempt to take bookings or payments off-platform to
                        avoid fees, or solicit travellers for that purpose;
                    </li>
                    <li>
                        harass, threaten or discriminate against other users;
                    </li>
                    <li>
                        scrape, reverse engineer, overload or interfere with the
                        Service, or access it other than through the interfaces
                        we provide;
                    </li>
                    <li>
                        upload malware or content you have no right to share.
                    </li>
                </ul>
            </LegalSection>

            <LegalSection heading="9. Advertising and third-party services">
                <p>
                    The Service displays advertising supplied by third-party
                    networks (for example Adsterra and Google) and may link to
                    third-party websites or services. We do not control and are
                    not responsible for third-party content, offers or
                    practices. Ads are labelled or served in a way that keeps
                    them distinct from our own content, and our admin tools and
                    partner panels do not carry advertising.
                </p>
            </LegalSection>

            <LegalSection heading="10. Intellectual property">
                <p>
                    The Service, including the BookTrips name and logo, the site
                    design, text and software, is owned by Caymass Holidays Pvt
                    Ltd or its licensors and is protected by law. We grant you a
                    limited, non-transferable licence to use the Service for its
                    intended purpose. You may not copy, modify, distribute or
                    resell any part of the Service without our written
                    permission.
                </p>
            </LegalSection>

            <LegalSection heading="11. Disclaimers">
                <p>
                    The Service is provided “as is” and “as available”. We do
                    not guarantee that a listing will be accurate at every
                    moment, that a host will accept your request, or that a trip
                    will meet your expectations — hosts are independent
                    businesses. Travel involves risk; you are responsible for
                    your own safety, insurance and compliance with local rules
                    during an activity.
                </p>
            </LegalSection>

            <LegalSection heading="12. Limitation of liability">
                <p>
                    To the maximum extent permitted by law, Caymass Holidays Pvt
                    Ltd is not liable for indirect, incidental or consequential
                    losses, loss of profits or data, or losses arising from the
                    acts or omissions of hosts or travellers. Where we are found
                    liable, our total liability is limited to the greater of the
                    fees you paid to us in the twelve months before the claim or
                    LKR 10,000. Nothing in these Terms excludes liability that
                    cannot be excluded by law.
                </p>
            </LegalSection>

            <LegalSection heading="13. Suspension and termination">
                <p>
                    We may suspend or terminate accounts that breach these
                    Terms, harm other users or the platform, or where we are
                    required to by law. You can stop using the Service at any
                    time and ask us to close your account; records we must keep
                    for legal reasons will be retained as described in the
                    Privacy Policy.
                </p>
            </LegalSection>

            <LegalSection heading="14. Changes to these Terms">
                <p>
                    We may update these Terms from time to time by posting the
                    new version on this page and updating the date at the top.
                    Material changes will be notified by email or a prominent
                    notice on the Service. Continuing to use the Service after a
                    change takes effect means you accept the updated Terms.
                </p>
            </LegalSection>

            <LegalSection heading="15. Governing law">
                <p>
                    These Terms are governed by the laws of Sri Lanka, and
                    disputes about them are subject to the exclusive
                    jurisdiction of the courts of Colombo, Sri Lanka, unless a
                    mandatory law in your country of residence gives you other
                    rights.
                </p>
            </LegalSection>

            <LegalSection heading="16. Contact us">
                <p>
                    Questions about these Terms? Write to us at{' '}
                    <a
                        href="mailto:artismhr@yahoo.com"
                        className="text-brand-800 font-bold hover:underline"
                    >
                        artismhr@yahoo.com
                    </a>
                    , call +94 70 346 4477, or use our{' '}
                    <Link
                        href="/support"
                        className="text-brand-800 font-bold hover:underline"
                    >
                        support page
                    </Link>
                    . You can also write to 230/24, Asoka Uyana, Thalgasgoda,
                    Ambalangoda, Sri Lanka.
                </p>
            </LegalSection>
        </LegalShell>
    );
};

Terms.layout = withAppLayout;

export default Terms;
