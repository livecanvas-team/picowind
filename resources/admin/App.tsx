import { AppShell } from "@/components/blocks/app-shell-1/components/app-shell"
import { Dashboard } from "@/components/blocks/dashboard-1/components/dashboard"

import { useDashboard } from "@/admin/hooks/use-dashboard"

export default function App() {
  const controller = useDashboard()

  return (
    <AppShell
      refreshing={controller.refreshing}
      onRefresh={() => void controller.refresh(true)}
    >
      <Dashboard controller={controller} />
    </AppShell>
  )
}
