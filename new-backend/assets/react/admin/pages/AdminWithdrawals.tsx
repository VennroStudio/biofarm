import { useState } from "react";
import { withdrawalsApi } from "../api/resources";
import { ProcessedWithdrawalsTable } from "../features/withdrawals/ui/ProcessedWithdrawalsTable";
import { useLoadOnMount } from "../hooks/useLoadOnMount";
import { PageHeader } from "../shared/ui";
import type { Withdrawal } from "../types";
export function AdminWithdrawals() {
    const [rows, setRows] = useState<Withdrawal[]>([]);
    useLoadOnMount(async () => {
        setRows((await withdrawalsApi.list()).items);
    });
    return (
        <>
            <PageHeader title="История прежних заявок" subtitle="Исторические записи доступны только для чтения" />
            <a href="/admin/program" className="my-4 inline-block underline">
                Открыть новую очередь ручных выплат
            </a>
            <ProcessedWithdrawalsTable withdrawals={rows} />
        </>
    );
}
