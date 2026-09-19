const RULES = [
    { key: 'length', label: '8+ characters' },
    { key: 'upper', label: 'Uppercase letter' },
    { key: 'lower', label: 'Lowercase letter' },
    { key: 'number', label: 'Number' },
    { key: 'special', label: 'Symbol (!@#$…)' },
] as const;

export function passwordChecks(password: string) {
    const value = String(password || '');

    return {
        length: value.length >= 8,
        upper: /[A-Z]/.test(value),
        lower: /[a-z]/.test(value),
        number: /\d/.test(value),
        special: /[^A-Za-z0-9]/.test(value),
    };
}

export function passwordOk(password: string): boolean {
    return Object.values(passwordChecks(password)).every(Boolean);
}

export default function PasswordRules({ password }: { password: string }) {
    const checks = passwordChecks(password);

    return (
        <ul className="text-muted mb-3 grid list-none gap-1 p-0 text-xs">
            {RULES.map((rule) => (
                <li
                    key={rule.key}
                    className={
                        checks[rule.key]
                            ? 'text-brand-800 font-bold'
                            : undefined
                    }
                >
                    {rule.label}
                </li>
            ))}
        </ul>
    );
}
