import LegalShell, { LegalSection } from '@/components/booktrips/legal-shell';
import { withAppLayout } from '@/layouts/app-layout';
import type { InertiaComponent } from '@/types/inertia';

const Privacy: InertiaComponent<Record<string, never>> = () => {
    return (
        <LegalShell
            title="Privacy Policy"
            updated="19 September 2026"
            intro="This Privacy Policy describes how Caymass Holidays Pvt Ltd (“we”, “us” or “our”) collects, uses and discloses your personal information when you use BookTrips (booktrips.lk, the “Service”). We take your privacy seriously and explain below, as clearly as possible, what we collect, why we collect it and the rights you have over it. It applies to all information collected through the Service and any related services, sales, marketing or events."
        >
            <LegalSection heading="1. Who we are">
                <p>
                    The Service is operated by Caymass Holidays Pvt Ltd, a
                    company based in Sri Lanka. When this policy mentions
                    “Company”, “we”, “us” or “our”, it refers to Caymass
                    Holidays Pvt Ltd; “you” refers to the person or organisation
                    registered with us to use the Service; “Website” refers to
                    booktrips.lk; and “Service” refers to the BookTrips
                    marketplace.
                </p>
            </LegalSection>

            <LegalSection heading="2. Information we collect">
                <p>We collect the following categories of information:</p>
                <ul className="list-disc space-y-1 pl-5">
                    <li>
                        <strong>Account details</strong> — your name, email
                        address and phone number when you register or apply to
                        become a partner. Passwords are stored only as secure
                        hashes.
                    </li>
                    <li>
                        <strong>Booking details</strong> — guest name, contact
                        number, travel dates, number of guests and any notes you
                        add when you request a booking.
                    </li>
                    <li>
                        <strong>Payment information</strong> — bookings are paid
                        at the destination, so we do not collect card numbers.
                        Payment receipts that partners upload for our records
                        are stored in private storage and only visible to the
                        business and our team.
                    </li>
                    <li>
                        <strong>Photos and content</strong> — images,
                        descriptions and reviews uploaded by partners and
                        travellers.
                    </li>
                    <li>
                        <strong>Usage and device data</strong> — IP address,
                        browser type and version, pages visited, time and date
                        of visits, time spent on pages, unique device
                        identifiers and other diagnostic data.
                    </li>
                    <li>
                        <strong>Location data</strong> — approximate location,
                        but only when you give permission through your device
                        (for example, to centre the map near you). You can
                        disable location services at any time in your device
                        settings.
                    </li>
                    <li>
                        <strong>Cookies and similar technologies</strong> — see
                        section 5 below.
                    </li>
                </ul>
            </LegalSection>

            <LegalSection heading="3. How we use your information">
                <ul className="list-disc space-y-1 pl-5">
                    <li>
                        To provide and maintain the Service, including
                        processing booking requests.
                    </li>
                    <li>
                        To communicate with you about bookings and your account
                        — confirmations, approvals, cancellations, reminders and
                        reports, by email and SMS.
                    </li>
                    <li>
                        To provide customer support and respond to your
                        questions.
                    </li>
                    <li>
                        To detect, prevent and address fraud, abuse, security
                        and technical issues.
                    </li>
                    <li>
                        To analyse how the Service is used and improve its
                        features and content.
                    </li>
                    <li>
                        To comply with legal obligations and enforce our
                        agreements.
                    </li>
                    <li>
                        To send news, special offers and information about
                        similar goods and services, unless you opt out.
                        Transactional messages about your bookings are part of
                        the Service and cannot be switched off while your
                        account is active.
                    </li>
                </ul>
            </LegalSection>

            <LegalSection heading="4. Information shared with hosts and partners">
                <p>
                    BookTrips connects travellers with hosts. When you request a
                    booking, we share the details the host needs to serve you —
                    your guest name, phone number, travel dates, guest count and
                    your notes — with that business and its staff. We do not
                    sell your personal information to anyone.
                </p>
            </LegalSection>

            <LegalSection heading="5. Cookies and tracking technologies">
                <p>
                    Cookies are small files placed on your device that enable
                    certain features and let us remember you between visits. We
                    use session cookies to operate the Service, preference
                    cookies to remember your settings, and security cookies for
                    protection against abuse. You can instruct your browser to
                    refuse all cookies or to alert you when one is being sent,
                    but parts of the Service may not work without them.
                </p>
                <p>
                    We may also allow third-party advertising networks (for
                    example Google and Adsterra) to set their own cookies or
                    device identifiers so they can show ads that are more
                    relevant to you, including ads based on your visits to this
                    and other websites. Ad networks operate under their own
                    privacy policies. You can opt out of personalised
                    advertising through the{' '}
                    <a
                        href="https://www.google.com/settings/ads"
                        target="_blank"
                        rel="noreferrer"
                        className="text-brand-800 font-bold hover:underline"
                    >
                        Google Ads Settings
                    </a>{' '}
                    page or{' '}
                    <a
                        href="https://www.aboutads.info/choices/"
                        target="_blank"
                        rel="noreferrer"
                        className="text-brand-800 font-bold hover:underline"
                    >
                        aboutads.info
                    </a>
                    .
                </p>
            </LegalSection>

            <LegalSection heading="6. Disclosure of your information">
                <p>We only share personal information in these situations:</p>
                <ul className="list-disc space-y-1 pl-5">
                    <li>
                        <strong>With the host you book</strong> — as described
                        in section 4.
                    </li>
                    <li>
                        <strong>With service providers</strong> who help us run
                        the Service (hosting, cloud storage, email and SMS
                        delivery, analytics). They may access your information
                        only to perform those tasks for us and must not use it
                        for anything else.
                    </li>
                    <li>
                        <strong>For legal reasons</strong> — to comply with the
                        law, respond to valid requests by public authorities,
                        protect the rights and property of Caymass Holidays Pvt
                        Ltd, prevent or investigate wrongdoing, protect the
                        safety of users or the public, or protect against legal
                        liability.
                    </li>
                    <li>
                        <strong>Business transfers</strong> — if we are involved
                        in a merger, acquisition or asset sale, your information
                        may be transferred. We will give notice before it
                        becomes subject to a different privacy policy.
                    </li>
                </ul>
            </LegalSection>

            <LegalSection heading="7. Retention of your data">
                <p>
                    We keep personal data only for as long as necessary for the
                    purposes described in this policy. Booking and billing
                    records are retained longer where we must comply with tax,
                    accounting and other legal obligations, resolve disputes and
                    enforce our agreements. Usage data is generally kept for a
                    shorter period unless it is needed to strengthen security or
                    improve the Service.
                </p>
            </LegalSection>

            <LegalSection heading="8. International transfers">
                <p>
                    Your information may be transferred to — and stored on —
                    computers located outside Sri Lanka, including cloud
                    infrastructure we use to run the Service, where data
                    protection laws may differ. When you provide information to
                    us, you consent to that transfer. We take reasonable steps
                    to ensure your data is treated securely and in line with
                    this policy wherever it is processed.
                </p>
            </LegalSection>

            <LegalSection heading="9. Security">
                <p>
                    The security of your personal data matters to us, but no
                    method of transmission over the internet and no method of
                    electronic storage is completely secure. We use commercially
                    acceptable means — encrypted connections, hashed passwords,
                    private storage for documents — to protect your information,
                    and we cannot promise absolute security.
                </p>
            </LegalSection>

            <LegalSection heading="10. Children’s privacy">
                <p>
                    The Service is not directed to anyone under the age of 13,
                    and we do not knowingly collect personal data from children
                    under 13. If you are a parent or guardian and believe your
                    child has provided us with personal data, please contact us
                    and we will delete it. Where your country requires parental
                    consent for us to process information, we may ask for that
                    consent first.
                </p>
            </LegalSection>

            <LegalSection heading="11. Your data protection rights">
                <p>
                    Depending on where you live, you may have the right to
                    access, correct, delete or restrict the use of your personal
                    data, and to object to or withdraw consent for certain
                    processing. We honour reasonable requests wherever you are
                    located:
                </p>
                <ul className="list-disc space-y-1 pl-5">
                    <li>
                        <strong>Access</strong> — you can ask for a copy of the
                        personal information we hold about you, in a structured,
                        commonly used, machine-readable format.
                    </li>
                    <li>
                        <strong>Correction</strong> — you can update most
                        details from your account page, or ask us to correct
                        anything that is wrong.
                    </li>
                    <li>
                        <strong>Deletion</strong> — you can ask us to delete
                        your personal information. We will do so unless we are
                        legally required to keep certain records (for example,
                        booking records for tax purposes).
                    </li>
                </ul>
                <p>
                    To make a request, contact us using the details at the end
                    of this policy. We respond within 30 days and may need to
                    verify your identity first. If you are unhappy with our
                    response, you can complain to your local data protection
                    authority.
                </p>
            </LegalSection>

            <LegalSection heading="12. Links to other sites">
                <p>
                    The Service may contain links to websites we do not operate.
                    If you click a third-party link, you will be taken to that
                    site, and we strongly advise you to review their privacy
                    policy. We have no control over, and assume no
                    responsibility for, the content, privacy policies or
                    practices of third-party sites or services.
                </p>
            </LegalSection>

            <LegalSection heading="13. Changes to this policy">
                <p>
                    We may update this Privacy Policy from time to time by
                    posting the new version on this page and updating the date
                    at the top. For material changes we will also notify you by
                    email or with a prominent notice on the Service before the
                    change takes effect. You are advised to review this page
                    periodically; changes are effective when posted.
                </p>
            </LegalSection>

            <LegalSection heading="14. Contact us">
                <p>
                    If you have any questions about this Privacy Policy, contact
                    us:
                </p>
                <ul className="list-disc space-y-1 pl-5">
                    <li>
                        By email:{' '}
                        <a
                            href="mailto:artismhr@yahoo.com"
                            className="text-brand-800 font-bold hover:underline"
                        >
                            artismhr@yahoo.com
                        </a>
                    </li>
                    <li>By phone: +94 70 346 4477</li>
                    <li>
                        By mail: 230/24, Asoka Uyana, Thalgasgoda, Ambalangoda,
                        Sri Lanka
                    </li>
                </ul>
            </LegalSection>
        </LegalShell>
    );
};

Privacy.layout = withAppLayout;

export default Privacy;
