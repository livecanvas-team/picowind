import { useState, type ReactNode } from "react"
import {
  ExternalLinkIcon,
  LayoutDashboardIcon,
  MenuIcon,
  PaletteIcon,
  PlugZapIcon,
  RefreshCwIcon,
  UserRoundIcon,
  ZapIcon,
} from "lucide-react"

import { Badge } from "@/components/reui/badge"
import { Button } from "@/components/ui/button"
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet"
import { cn } from "@/lib/utils"

const navigation = [
  { label: "Overview", href: "#overview", icon: LayoutDashboardIcon },
  { label: "Child themes", href: "#themes", icon: PaletteIcon },
  { label: "Plugins", href: "#plugins", icon: PlugZapIcon },
]

function Brand() {
  return (
    <div className="flex items-center gap-3 px-3 py-2">
      <div className="flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm shadow-primary/20">
        <ZapIcon className="size-4 fill-current" aria-hidden="true" />
      </div>
      <div className="min-w-0">
        <div className="flex items-center gap-2">
          <p className="truncate text-sm font-semibold tracking-tight">Picowind</p>
          <Badge variant="primary-light" size="sm" radius="full">
            v{window.picowind?._version || "—"}
          </Badge>
        </div>
        <p className="truncate text-xs text-muted-foreground">Theme control center</p>
      </div>
    </div>
  )
}

function Navigation({ onNavigate }: { onNavigate?: () => void }) {
  return (
    <nav className="space-y-1" aria-label="Picowind dashboard navigation">
      {navigation.map((item, index) => (
        <a
          key={item.href}
          href={item.href}
          onClick={onNavigate}
          className={cn(
            "group flex h-9 items-center gap-3 rounded-lg px-3 text-sm font-medium no-underline transition-colors",
            index === 0
              ? "bg-sidebar-accent text-sidebar-accent-foreground"
              : "text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
          )}
        >
          <item.icon className="size-4 shrink-0" aria-hidden="true" />
          <span>{item.label}</span>
        </a>
      ))}
    </nav>
  )
}

function UserCard() {
  const user = window.picowind?.current_user
  const role = user?.role ? user.role.replaceAll("_", " ") : "Administrator"

  return (
    <div className="flex items-center gap-3 rounded-xl border border-sidebar-border bg-sidebar-accent/45 p-2.5">
      {user?.avatar ? (
        <img
          src={user.avatar}
          alt=""
          className="size-8 rounded-lg border border-sidebar-border object-cover"
        />
      ) : (
        <div className="flex size-8 items-center justify-center rounded-lg bg-background text-muted-foreground">
          <UserRoundIcon className="size-4" aria-hidden="true" />
        </div>
      )}
      <div className="min-w-0">
        <p className="truncate text-xs font-semibold text-sidebar-foreground">
          {user?.name || "Site administrator"}
        </p>
        <p className="truncate text-[11px] capitalize text-muted-foreground">{role}</p>
      </div>
    </div>
  )
}

function SidebarContent({ onNavigate }: { onNavigate?: () => void }) {
  return (
    <div className="flex h-full flex-col bg-sidebar text-sidebar-foreground">
      <div className="border-b border-sidebar-border p-3">
        <Brand />
      </div>
      <div className="flex-1 p-3">
        <p className="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
          Workspace
        </p>
        <Navigation onNavigate={onNavigate} />
      </div>
      <div className="border-t border-sidebar-border p-3">
        <UserCard />
      </div>
    </div>
  )
}

interface AppShellProps {
  children: ReactNode
  refreshing: boolean
  onRefresh: () => void
}

export function AppShell({ children, refreshing, onRefresh }: AppShellProps) {
  const [mobileNavigationOpen, setMobileNavigationOpen] = useState(false)
  const siteName = window.picowind?.site_meta?.name || "WordPress site"
  const siteUrl = window.picowind?.site_meta?.site_url || "/"

  return (
    <div className="picowind-dashboard flex min-h-full bg-background text-foreground">
      <aside className="hidden w-60 shrink-0 border-r border-sidebar-border md:block">
        <div className="sticky top-0 h-[calc(100vh-32px)]">
          <SidebarContent />
        </div>
      </aside>

      <Sheet open={mobileNavigationOpen} onOpenChange={setMobileNavigationOpen}>
        <SheetContent side="left" className="w-72 p-0">
          <SheetHeader className="sr-only">
            <SheetTitle>Picowind navigation</SheetTitle>
            <SheetDescription>Navigate the Picowind theme dashboard.</SheetDescription>
          </SheetHeader>
          <SidebarContent onNavigate={() => setMobileNavigationOpen(false)} />
        </SheetContent>
      </Sheet>

      <div className="min-w-0 flex-1">
        <header className="sticky top-0 z-20 flex h-14 items-center justify-between gap-3 border-b border-border/80 bg-background/90 px-4 backdrop-blur-xl sm:px-6">
          <div className="flex min-w-0 items-center gap-3">
            <Button
              variant="ghost"
              size="icon"
              className="md:hidden"
              aria-label="Open dashboard navigation"
              onClick={() => setMobileNavigationOpen(true)}
            >
              <MenuIcon />
            </Button>
            <div className="min-w-0">
              <p className="truncate text-sm font-semibold">{siteName}</p>
              <p className="hidden truncate text-xs text-muted-foreground sm:block">
                Appearance / Picowind
              </p>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="icon"
              aria-label="Refresh dashboard"
              title="Refresh dashboard"
              disabled={refreshing}
              onClick={onRefresh}
            >
              <RefreshCwIcon className={cn(refreshing && "animate-spin")} />
            </Button>
            <a
              href={siteUrl}
              target="_blank"
              rel="noreferrer"
              className="inline-flex h-8 items-center gap-1.5 rounded-lg border border-border bg-background px-2.5 text-sm font-medium text-foreground no-underline transition-colors hover:bg-muted"
            >
              <span className="hidden sm:inline">View site</span>
              <ExternalLinkIcon className="size-3.5" aria-hidden="true" />
            </a>
          </div>
        </header>

        <main className="mx-auto w-full max-w-[1440px] p-4 sm:p-6 lg:p-8">
          {children}
        </main>
      </div>
    </div>
  )
}
