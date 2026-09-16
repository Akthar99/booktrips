import { useEffect } from 'react';
import Button from '@/components/booktrips/button';

export default function ConfirmDialog({
    open,
    title,
    message,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    danger = false,
    busy = false,
    onConfirm,
    onCancel,
}: {
    open: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    danger?: boolean;
    busy?: boolean;
    onConfirm: () => void;
    onCancel: () => void;
}) {
    useEffect(() => {
        if (!open) {
            return undefined;
        }

        function onKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape' && !busy) {
                onCancel();
            }
        }

        document.addEventListener('keydown', onKeyDown);

        return () => document.removeEventListener('keydown', onKeyDown);
    }, [open, busy, onCancel]);

    if (!open) {
        return null;
    }

    return (
        <div
            className="fixed inset-0 z-[120] flex items-center justify-center bg-navy-950/45 p-5"
            role="presentation"
            onMouseDown={(event) => {
                if (event.target === event.currentTarget && !busy) {
                    onCancel();
                }
            }}
        >
            <div
                className="w-full max-w-[420px] rounded-[18px] border border-line bg-white p-6 shadow-[0_24px_70px_rgba(7,18,33,0.2)]"
                role="dialog"
                aria-modal="true"
                aria-labelledby="confirm-title"
            >
                <h2 id="confirm-title" className="mb-2 text-2xl">
                    {title}
                </h2>
                <p className="text-sm text-muted">{message}</p>
                <div className="mt-5 flex justify-end gap-2">
                    <Button variant="outline" onClick={onCancel} disabled={busy}>
                        {cancelLabel}
                    </Button>
                    <Button variant={danger ? 'danger' : 'primary'} onClick={onConfirm} disabled={busy}>
                        {busy ? 'Please wait…' : confirmLabel}
                    </Button>
                </div>
            </div>
        </div>
    );
}
