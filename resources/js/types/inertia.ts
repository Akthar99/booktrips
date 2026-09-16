import type { ReactNode } from 'react';

export type InertiaComponent<Props> = ((props: Props) => ReactNode) & {
    layout?: (page: ReactNode) => ReactNode;
};
