import { Card } from "../../../shared/ui";
export function ReferralSettingsCard() {
    return (
        <Card className="space-y-3 p-6">
            <h2 className="text-xl">Реферальная программа</h2>
            <a className="underline" href="/admin/program">
                Управление четырьмя уровнями, партнёрской ставкой и бюджетом
            </a>
        </Card>
    );
}
