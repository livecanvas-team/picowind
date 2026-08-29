export interface ChildThemeStatus {
  name: string
  slug: string
  version: string
  parent: string
}

export interface DashboardStatus {
  completed: boolean
  hasChildTheme: boolean
  childTheme: ChildThemeStatus | null
}

export interface BundledTheme {
  id: string
  name: string
  description: string
  version: string
  author: string
  themeUri?: string
  template?: string
  textDomain?: string
  tags: string[]
  installed: boolean
  active: boolean
  installedSlug: string | null
}

export interface RecommendedPlugin {
  id: string
  name: string
  slug: string
  source: "wporg" | "external"
  url: string
  description: string
  installed: boolean
  active: boolean
}

export interface DashboardSummary {
  setupProgress: number
  readyItems: number
  totalItems: number
  installedThemes: number
  availableThemes: number
  activePlugins: number
  recommendedPlugins: number
}

export interface DashboardData {
  status: DashboardStatus
  themes: BundledTheme[]
  plugins: RecommendedPlugin[]
  summary: DashboardSummary
}

export type DashboardAction =
  | { type: "install-theme"; id: string }
  | { type: "activate-theme"; id: string; slug: string }
  | { type: "install-plugin"; id: string; slug: string }
  | { type: "activate-plugin"; id: string; slug: string }
  | { type: "complete"; id: "setup" }

export function getActionKey(action: DashboardAction) {
  return `${action.type}:${action.id}`
}
