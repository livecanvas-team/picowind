import { useCallback, useEffect, useState } from "react"

import {
  fetchDashboard,
  getApiErrorMessage,
  runDashboardAction,
} from "@/admin/library/api"
import {
  getActionKey,
  type DashboardAction,
  type DashboardData,
} from "@/admin/types"

export function useDashboard() {
  const [data, setData] = useState<DashboardData | null>(null)
  const [loading, setLoading] = useState(true)
  const [refreshing, setRefreshing] = useState(false)
  const [actionKey, setActionKey] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [notice, setNotice] = useState<string | null>(null)

  const refresh = useCallback(async (quiet = false) => {
    if (quiet) {
      setRefreshing(true)
    } else {
      setLoading(true)
    }
    setError(null)

    try {
      setData(await fetchDashboard())
    } catch (requestError) {
      setError(getApiErrorMessage(requestError))
    } finally {
      setLoading(false)
      setRefreshing(false)
    }
  }, [])

  useEffect(() => {
    void refresh()
  }, [refresh])

  const runAction = useCallback(
    async (action: DashboardAction) => {
      setActionKey(getActionKey(action))
      setError(null)
      setNotice(null)

      try {
        setNotice(await runDashboardAction(action))
        await refresh(true)
      } catch (requestError) {
        setError(getApiErrorMessage(requestError))
      } finally {
        setActionKey(null)
      }
    },
    [refresh]
  )

  return {
    data,
    loading,
    refreshing,
    actionKey,
    error,
    notice,
    refresh,
    runAction,
    dismissNotice: () => setNotice(null),
  }
}

export type DashboardController = ReturnType<typeof useDashboard>
