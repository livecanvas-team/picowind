import type { LucideIcon } from "lucide-react"
import {
  CheckCircle2Icon,
  CircleAlertIcon,
  CircleDotDashedIcon,
  ExternalLinkIcon,
  LoaderCircleIcon,
  PaletteIcon,
  PlugZapIcon,
  RocketIcon,
  ShieldCheckIcon,
  SparklesIcon,
  ZapIcon,
} from "lucide-react"

import type { DashboardController } from "@/admin/hooks/use-dashboard"
import {
  getActionKey,
  type BundledTheme,
  type DashboardAction,
  type RecommendedPlugin,
} from "@/admin/types"
import { Badge } from "@/components/reui/badge"
import { Frame, FramePanel } from "@/components/reui/frame"
import { Button } from "@/components/ui/button"
import { Progress } from "@/components/ui/progress"
import { Skeleton } from "@/components/ui/skeleton"
import { cn } from "@/lib/utils"

interface DashboardProps {
  controller: DashboardController
}

interface MetricCardProps {
  icon: LucideIcon
  label: string
  value: string
  detail: string
  tone: "blue" | "violet" | "emerald" | "amber"
}

const metricTone = {
  blue: "bg-blue-500/10 text-blue-700 dark:text-blue-300",
  violet: "bg-violet-500/10 text-violet-700 dark:text-violet-300",
  emerald: "bg-emerald-500/10 text-emerald-700 dark:text-emerald-300",
  amber: "bg-amber-500/10 text-amber-700 dark:text-amber-300",
}

function MetricCard({ icon: Icon, label, value, detail, tone }: MetricCardProps) {
  return (
    <FramePanel className="min-h-36">
      <div className="flex items-start justify-between gap-4">
        <div className={cn("flex size-9 items-center justify-center rounded-lg", metricTone[tone])}>
          <Icon className="size-4" aria-hidden="true" />
        </div>
        <span className="text-[10px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">
          Live
        </span>
      </div>
      <p className="mt-5 text-xs font-medium text-muted-foreground">{label}</p>
      <p className="mt-1 truncate text-xl font-semibold tracking-tight">{value}</p>
      <p className="mt-1 text-xs text-muted-foreground">{detail}</p>
    </FramePanel>
  )
}

function SectionHeading({
  eyebrow,
  title,
  description,
  trailing,
}: {
  eyebrow: string
  title: string
  description: string
  trailing?: React.ReactNode
}) {
  return (
    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p className="text-[10px] font-semibold uppercase tracking-[0.18em] text-primary">{eyebrow}</p>
        <h2 className="mt-1.5 text-xl font-semibold tracking-tight">{title}</h2>
        <p className="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground">{description}</p>
      </div>
      {trailing}
    </div>
  )
}

function ActionButton({
  controller,
  action,
  label,
  busyLabel,
  variant = "default",
}: {
  controller: DashboardController
  action: DashboardAction
  label: string
  busyLabel: string
  variant?: "default" | "outline" | "secondary"
}) {
  const key = getActionKey(action)
  const busy = controller.actionKey === key

  return (
    <Button
      size="sm"
      variant={variant}
      disabled={controller.actionKey !== null}
      onClick={() => void controller.runAction(action)}
    >
      {busy && <LoaderCircleIcon className="animate-spin" aria-hidden="true" />}
      {busy ? busyLabel : label}
    </Button>
  )
}

function StatusNotice({ controller }: { controller: DashboardController }) {
  if (!controller.error && !controller.notice) {
    return null
  }

  const isError = Boolean(controller.error)

  return (
    <div
      role={isError ? "alert" : "status"}
      className={cn(
        "flex items-start gap-3 rounded-xl border px-4 py-3 text-sm",
        isError
          ? "border-destructive/20 bg-destructive/5 text-destructive-foreground"
          : "border-success/20 bg-success/5 text-success-foreground"
      )}
    >
      {isError ? (
        <CircleAlertIcon className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
      ) : (
        <CheckCircle2Icon className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
      )}
      <p className="flex-1">{controller.error || controller.notice}</p>
      {!isError && (
        <button
          type="button"
          className="text-xs font-semibold underline-offset-4 hover:underline"
          onClick={controller.dismissNotice}
        >
          Dismiss
        </button>
      )}
    </div>
  )
}

function LoadingDashboard() {
  return (
    <div className="space-y-6" aria-label="Loading Picowind dashboard">
      <div className="space-y-3">
        <Skeleton className="h-5 w-28" />
        <Skeleton className="h-10 w-full max-w-md" />
        <Skeleton className="h-5 w-full max-w-2xl" />
      </div>
      <Frame className="grid gap-1 sm:grid-cols-2 xl:grid-cols-4">
        {[0, 1, 2, 3].map((item) => (
          <FramePanel key={item} className="min-h-36 space-y-4">
            <Skeleton className="size-9 rounded-lg" />
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-7 w-40" />
          </FramePanel>
        ))}
      </Frame>
      <Skeleton className="h-72 w-full rounded-xl" />
    </div>
  )
}

function ThemeCard({ theme, controller }: { theme: BundledTheme; controller: DashboardController }) {
  return (
    <Frame spacing="sm" className="h-full">
      <FramePanel className="flex min-h-72 flex-col">
        <div className="flex items-start justify-between gap-4">
          <div className="flex size-11 items-center justify-center rounded-xl border border-border bg-muted/60 text-foreground shadow-xs">
            <PaletteIcon className="size-5" aria-hidden="true" />
          </div>
          {theme.active ? (
            <Badge variant="success-light" radius="full">Active</Badge>
          ) : theme.installed ? (
            <Badge variant="info-light" radius="full">Installed</Badge>
          ) : (
            <Badge variant="warning-light" radius="full">Available</Badge>
          )}
        </div>

        <div className="mt-5">
          <h3 className="text-base font-semibold tracking-tight">{theme.name}</h3>
          <p className="mt-2 line-clamp-3 text-sm leading-6 text-muted-foreground">
            {theme.description || "A bundled child theme crafted for Picowind."}
          </p>
        </div>

        {theme.tags?.length > 0 && (
          <div className="mt-4 flex flex-wrap gap-1.5">
            {theme.tags.slice(0, 3).map((tag) => (
              <Badge key={tag} variant="outline" size="sm" radius="full">
                {tag}
              </Badge>
            ))}
          </div>
        )}

        <div className="mt-auto flex items-end justify-between gap-4 border-t border-border/70 pt-5">
          <div className="text-xs text-muted-foreground">
            <p>Version {theme.version || "—"}</p>
            {theme.author && <p className="mt-1 truncate">By {theme.author}</p>}
          </div>

          {!theme.active && !theme.installed && (
            <ActionButton
              controller={controller}
              action={{ type: "install-theme", id: theme.id }}
              label="Install"
              busyLabel="Installing"
            />
          )}
          {!theme.active && theme.installed && theme.installedSlug && (
            <ActionButton
              controller={controller}
              action={{ type: "activate-theme", id: theme.id, slug: theme.installedSlug }}
              label="Activate"
              busyLabel="Activating"
            />
          )}
          {theme.active && (
            <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-success-foreground">
              <CheckCircle2Icon className="size-3.5" aria-hidden="true" />
              In use
            </span>
          )}
        </div>
      </FramePanel>
    </Frame>
  )
}

function PluginRow({ plugin, controller }: { plugin: RecommendedPlugin; controller: DashboardController }) {
  return (
    <FramePanel className="flex flex-col gap-4 sm:flex-row sm:items-center">
      <div className="flex min-w-0 flex-1 items-start gap-3.5">
        <div className="flex size-10 shrink-0 items-center justify-center rounded-xl border border-border bg-muted/60 text-muted-foreground shadow-xs">
          <PlugZapIcon className="size-4.5" aria-hidden="true" />
        </div>
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            <h3 className="text-sm font-semibold">{plugin.name}</h3>
            {plugin.active ? (
              <Badge variant="success-light" size="sm" radius="full">Active</Badge>
            ) : plugin.installed ? (
              <Badge variant="info-light" size="sm" radius="full">Installed</Badge>
            ) : plugin.source === "external" ? (
              <Badge variant="invert-light" size="sm" radius="full">Partner</Badge>
            ) : (
              <Badge variant="warning-light" size="sm" radius="full">Recommended</Badge>
            )}
          </div>
          <p className="mt-1 line-clamp-2 text-sm leading-5 text-muted-foreground">{plugin.description}</p>
        </div>
      </div>

      <div className="flex shrink-0 items-center justify-end gap-2 border-t border-border/70 pt-3 sm:border-0 sm:pt-0">
        {plugin.url && (
          <a
            href={plugin.url}
            target="_blank"
            rel="noreferrer"
            aria-label={`Open ${plugin.name} website`}
            title={`Open ${plugin.name} website`}
            className="inline-flex size-7 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
          >
            <ExternalLinkIcon className="size-3.5" aria-hidden="true" />
          </a>
        )}

        {!plugin.installed && plugin.source === "wporg" && (
          <ActionButton
            controller={controller}
            action={{ type: "install-plugin", id: plugin.id, slug: plugin.slug }}
            label="Install"
            busyLabel="Installing"
            variant="outline"
          />
        )}
        {!plugin.installed && plugin.source === "external" && plugin.url && (
          <a
            href={plugin.url}
            target="_blank"
            rel="noreferrer"
            className="inline-flex h-7 items-center rounded-lg bg-secondary px-2.5 text-[0.8rem] font-medium text-secondary-foreground no-underline transition-colors hover:bg-muted"
          >
            Visit plugin
          </a>
        )}
        {plugin.installed && !plugin.active && (
          <ActionButton
            controller={controller}
            action={{ type: "activate-plugin", id: plugin.id, slug: plugin.slug }}
            label="Activate"
            busyLabel="Activating"
          />
        )}
        {plugin.active && (
          <span className="inline-flex h-7 items-center gap-1.5 px-1 text-xs font-semibold text-success-foreground">
            <CheckCircle2Icon className="size-3.5" aria-hidden="true" />
            Ready
          </span>
        )}
      </div>
    </FramePanel>
  )
}

export function Dashboard({ controller }: DashboardProps) {
  if (controller.loading && !controller.data) {
    return <LoadingDashboard />
  }

  if (!controller.data) {
    return (
      <Frame>
        <FramePanel className="flex min-h-72 flex-col items-center justify-center text-center">
          <div className="flex size-11 items-center justify-center rounded-xl bg-destructive/10 text-destructive">
            <CircleAlertIcon className="size-5" aria-hidden="true" />
          </div>
          <h1 className="mt-4 text-lg font-semibold">Dashboard unavailable</h1>
          <p className="mt-1 max-w-md text-sm text-muted-foreground">
            {controller.error || "Picowind could not load the current WordPress setup."}
          </p>
          <Button className="mt-5" onClick={() => void controller.refresh()}>
            Try again
          </Button>
        </FramePanel>
      </Frame>
    )
  }

  const { status, summary, themes, plugins } = controller.data
  const setupReady = summary.setupProgress === 100
  const activeThemeName = status.childTheme?.name || "Parent theme"

  return (
    <div className="space-y-10 pb-10">
      <section id="overview" className="scroll-mt-20 space-y-6" aria-labelledby="overview-title">
        <div className="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <Badge variant={setupReady ? "success-light" : "warning-light"} radius="full">
                {setupReady ? "Site ready" : "Setup in progress"}
              </Badge>
              <Badge variant="outline" radius="full">WordPress {window.picowind?._wp_version || "—"}</Badge>
            </div>
            <h1 id="overview-title" className="mt-4 text-3xl font-semibold tracking-[-0.035em] sm:text-4xl">
              Your Picowind workspace
            </h1>
            <p className="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base">
              Manage the theme foundation and the tools that power your site from one focused control center.
            </p>
          </div>

          {!status.completed && setupReady && (
            <ActionButton
              controller={controller}
              action={{ type: "complete", id: "setup" }}
              label="Finish setup"
              busyLabel="Finishing"
            />
          )}
          {status.completed && (
            <div className="inline-flex items-center gap-2 text-sm font-medium text-success-foreground">
              <ShieldCheckIcon className="size-4" aria-hidden="true" />
              Setup completed
            </div>
          )}
        </div>

        <StatusNotice controller={controller} />

        <Frame className="grid gap-1 sm:grid-cols-2 xl:grid-cols-4">
          <MetricCard
            icon={PaletteIcon}
            label="Active theme"
            value={activeThemeName}
            detail={status.childTheme ? `Child of ${status.childTheme.parent}` : "Choose a bundled child theme"}
            tone="blue"
          />
          <MetricCard
            icon={PlugZapIcon}
            label="Plugin readiness"
            value={`${summary.activePlugins} of ${summary.recommendedPlugins}`}
            detail="Recommended plugins active"
            tone="violet"
          />
          <MetricCard
            icon={ShieldCheckIcon}
            label="Setup health"
            value={`${summary.setupProgress}%`}
            detail={`${summary.readyItems} of ${summary.totalItems} checks ready`}
            tone="emerald"
          />
          <MetricCard
            icon={SparklesIcon}
            label="Theme library"
            value={`${summary.installedThemes} installed`}
            detail={`${summary.availableThemes} bundled options`}
            tone="amber"
          />
        </Frame>

        <Frame className="lg:grid lg:grid-cols-[1.2fr_0.8fr]" spacing="sm">
          <FramePanel>
            <div className="flex items-start justify-between gap-4">
              <div>
                <p className="text-sm font-semibold">Setup health</p>
                <p className="mt-1 text-sm text-muted-foreground">
                  Complete the foundation before moving into page building and customization.
                </p>
              </div>
              <span className="text-2xl font-semibold tracking-tight">{summary.setupProgress}%</span>
            </div>
            <Progress value={summary.setupProgress} className="mt-6" aria-label="Picowind setup progress" />
            <div className="mt-6 grid gap-3 sm:grid-cols-2">
              <div className="flex items-center gap-3 rounded-lg border border-border/70 bg-muted/30 p-3">
                {status.hasChildTheme ? (
                  <CheckCircle2Icon className="size-4 text-success-foreground" aria-hidden="true" />
                ) : (
                  <CircleDotDashedIcon className="size-4 text-warning-foreground" aria-hidden="true" />
                )}
                <div>
                  <p className="text-xs font-semibold">Child theme</p>
                  <p className="text-xs text-muted-foreground">
                    {status.hasChildTheme ? "Active and protected" : "Needs activation"}
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-3 rounded-lg border border-border/70 bg-muted/30 p-3">
                {summary.activePlugins === summary.recommendedPlugins ? (
                  <CheckCircle2Icon className="size-4 text-success-foreground" aria-hidden="true" />
                ) : (
                  <CircleDotDashedIcon className="size-4 text-warning-foreground" aria-hidden="true" />
                )}
                <div>
                  <p className="text-xs font-semibold">Plugin stack</p>
                  <p className="text-xs text-muted-foreground">
                    {summary.activePlugins} of {summary.recommendedPlugins} active
                  </p>
                </div>
              </div>
            </div>
          </FramePanel>

          <FramePanel className="flex flex-col justify-between bg-primary text-primary-foreground">
            <div>
              <div className="flex size-10 items-center justify-center rounded-xl bg-primary-foreground/10">
                <RocketIcon className="size-5" aria-hidden="true" />
              </div>
              <p className="mt-5 text-xs font-semibold uppercase tracking-[0.16em] text-primary-foreground/65">
                Next step
              </p>
              <h2 className="mt-2 text-xl font-semibold tracking-tight">
                {setupReady ? "Start building your site" : "Complete the foundation"}
              </h2>
              <p className="mt-2 text-sm leading-6 text-primary-foreground/75">
                {setupReady
                  ? "Your theme and plugin stack are ready for content and design work."
                  : "Activate a child theme and finish the recommended plugin setup below."}
              </p>
            </div>
            <a
              href={setupReady ? window.picowind.site_meta.site_url : "#themes"}
              target={setupReady ? "_blank" : undefined}
              rel={setupReady ? "noreferrer" : undefined}
              className="mt-6 inline-flex h-8 w-fit items-center gap-1.5 rounded-lg bg-primary-foreground px-3 text-sm font-semibold text-primary no-underline transition-opacity hover:opacity-90"
            >
              {setupReady ? "Open site" : "Continue setup"}
              <ZapIcon className="size-3.5" aria-hidden="true" />
            </a>
          </FramePanel>
        </Frame>
      </section>

      <section id="themes" className="scroll-mt-20 space-y-5" aria-labelledby="themes-title">
        <SectionHeading
          eyebrow="Foundation"
          title="Bundled child themes"
          description="Keep customizations upgrade-safe by installing and activating one of Picowind’s bundled child themes."
          trailing={
            <Badge variant="outline" radius="full">
              {themes.length} {themes.length === 1 ? "theme" : "themes"}
            </Badge>
          }
        />

        {themes.length > 0 ? (
          <div className="grid gap-4 lg:grid-cols-2 2xl:grid-cols-3">
            {themes.map((theme) => (
              <ThemeCard key={theme.id} theme={theme} controller={controller} />
            ))}
          </div>
        ) : (
          <Frame>
            <FramePanel className="py-12 text-center">
              <PaletteIcon className="mx-auto size-6 text-muted-foreground" aria-hidden="true" />
              <p className="mt-3 text-sm font-semibold">No bundled themes found</p>
              <p className="mt-1 text-sm text-muted-foreground">Add child themes to the Picowind bundle directory.</p>
            </FramePanel>
          </Frame>
        )}
      </section>

      <section id="plugins" className="scroll-mt-20 space-y-5" aria-labelledby="plugins-title">
        <SectionHeading
          eyebrow="Toolkit"
          title="Recommended plugins"
          description="A focused stack for visual building, Tailwind workflows, typography, and custom block development."
          trailing={
            <Badge variant={summary.activePlugins === plugins.length ? "success-light" : "outline"} radius="full">
              {summary.activePlugins}/{plugins.length} active
            </Badge>
          }
        />

        {plugins.length > 0 ? (
          <Frame spacing="sm" stacked>
            {plugins.map((plugin) => (
              <PluginRow key={plugin.id} plugin={plugin} controller={controller} />
            ))}
          </Frame>
        ) : (
          <Frame>
            <FramePanel className="py-12 text-center">
              <PlugZapIcon className="mx-auto size-6 text-muted-foreground" aria-hidden="true" />
              <p className="mt-3 text-sm font-semibold">No plugin recommendations yet</p>
              <p className="mt-1 text-sm text-muted-foreground">The dashboard is ready for a curated plugin stack.</p>
            </FramePanel>
          </Frame>
        )}
      </section>
    </div>
  )
}
