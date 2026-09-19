import { useCallback, useEffect, useState } from 'react';

/**
 * Simple countdown used to keep resend buttons honest.
 *
 * `start(seconds)` begins the countdown, `seconds` counts down to zero and
 * `active` is true while a resend should stay locked.
 */
export function useCountdown(defaultSeconds = 60) {
    const [until, setUntil] = useState<number | null>(null);
    const [seconds, setSeconds] = useState(0);

    useEffect(() => {
        if (until === null) {
            return undefined;
        }

        function tick() {
            const left = Math.max(
                0,
                Math.ceil(((until as number) - Date.now()) / 1000),
            );
            setSeconds(left);

            if (left === 0) {
                setUntil(null);
            }
        }

        tick();
        const timer = setInterval(tick, 500);

        return () => clearInterval(timer);
    }, [until]);

    const start = useCallback(
        (value = defaultSeconds) => {
            setSeconds(value);
            setUntil(Date.now() + value * 1000);
        },
        [defaultSeconds],
    );

    return { seconds, active: seconds > 0, start };
}
