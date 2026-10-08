import type { ComponentType, SVGProps } from 'react'
import ArrowBack from '@material-symbols/svg-400/outlined/arrow_back.svg?react'
import ArrowForward from '@material-symbols/svg-400/outlined/arrow_forward.svg?react'
import AutoAwesome from '@material-symbols/svg-400/outlined/star_shine.svg?react'
import Bookmark from '@material-symbols/svg-400/outlined/bookmark.svg?react'
import BookmarkFill from '@material-symbols/svg-400/outlined/bookmark-fill.svg?react'
import CalendarMonth from '@material-symbols/svg-400/outlined/calendar_month.svg?react'
import Check from '@material-symbols/svg-400/outlined/check.svg?react'
import CheckFill from '@material-symbols/svg-400/outlined/check-fill.svg?react'
import ChevronRight from '@material-symbols/svg-400/outlined/chevron_right.svg?react'
import Close from '@material-symbols/svg-400/outlined/close.svg?react'
import CloudOff from '@material-symbols/svg-400/outlined/cloud_off.svg?react'
import DarkMode from '@material-symbols/svg-400/outlined/dark_mode.svg?react'
import East from '@material-symbols/svg-400/outlined/east.svg?react'
import ErrorIcon from '@material-symbols/svg-400/outlined/error.svg?react'
import Explore from '@material-symbols/svg-400/outlined/explore.svg?react'
import ExploreFill from '@material-symbols/svg-400/outlined/explore-fill.svg?react'
import Home from '@material-symbols/svg-400/outlined/home.svg?react'
import HomeFill from '@material-symbols/svg-400/outlined/home-fill.svg?react'
import Insights from '@material-symbols/svg-400/outlined/monitoring.svg?react'
import IosShare from '@material-symbols/svg-400/outlined/ios_share.svg?react'
import Language from '@material-symbols/svg-400/outlined/language.svg?react'
import LightMode from '@material-symbols/svg-400/outlined/light_mode.svg?react'
import LocationOn from '@material-symbols/svg-400/outlined/location_on.svg?react'
import Notifications from '@material-symbols/svg-400/outlined/notifications.svg?react'
import NotificationsFill from '@material-symbols/svg-400/outlined/notifications-fill.svg?react'
import Person from '@material-symbols/svg-400/outlined/person.svg?react'
import PersonFill from '@material-symbols/svg-400/outlined/person-fill.svg?react'
import PlayArrowFill from '@material-symbols/svg-400/outlined/play_arrow-fill.svg?react'
import Refresh from '@material-symbols/svg-400/outlined/refresh.svg?react'
import Schedule from '@material-symbols/svg-400/outlined/schedule.svg?react'
import Search from '@material-symbols/svg-400/outlined/search.svg?react'
import Share from '@material-symbols/svg-400/outlined/share.svg?react'
import TrendingUp from '@material-symbols/svg-400/outlined/trending_up.svg?react'
import Tune from '@material-symbols/svg-400/outlined/tune.svg?react'
import VerifiedFill from '@material-symbols/svg-400/outlined/verified-fill.svg?react'
import Visibility from '@material-symbols/svg-400/outlined/visibility.svg?react'
import VisibilityOff from '@material-symbols/svg-400/outlined/visibility_off.svg?react'
import Campaign from '@material-symbols/svg-400/outlined/campaign.svg?react'
import DoneAll from '@material-symbols/svg-400/outlined/done_all.svg?react'
import Download from '@material-symbols/svg-400/outlined/download.svg?react'
import Newspaper from '@material-symbols/svg-400/outlined/newspaper.svg?react'
import NotificationsActive from '@material-symbols/svg-400/outlined/notifications_active.svg?react'
import NotificationsOff from '@material-symbols/svg-400/outlined/notifications_off.svg?react'
import { cn } from '@/lib/cn'

/**
 * Material Symbols Outlined (weight 400) as tree-shaken SVG components.
 * `-fill` variants are used for active/selected states, as in the designs.
 */
const ICONS = {
  arrow_back: ArrowBack,
  arrow_forward: ArrowForward,
  auto_awesome: AutoAwesome,
  bookmark: Bookmark,
  'bookmark-fill': BookmarkFill,
  calendar_month: CalendarMonth,
  check: Check,
  'check-fill': CheckFill,
  chevron_right: ChevronRight,
  close: Close,
  cloud_off: CloudOff,
  dark_mode: DarkMode,
  east: East,
  error: ErrorIcon,
  explore: Explore,
  'explore-fill': ExploreFill,
  home: Home,
  'home-fill': HomeFill,
  insights: Insights,
  ios_share: IosShare,
  language: Language,
  light_mode: LightMode,
  location_on: LocationOn,
  notifications: Notifications,
  'notifications-fill': NotificationsFill,
  person: Person,
  'person-fill': PersonFill,
  'play_arrow-fill': PlayArrowFill,
  refresh: Refresh,
  schedule: Schedule,
  search: Search,
  share: Share,
  trending_up: TrendingUp,
  tune: Tune,
  'verified-fill': VerifiedFill,
  visibility: Visibility,
  visibility_off: VisibilityOff,
  campaign: Campaign,
  done_all: DoneAll,
  download: Download,
  newspaper: Newspaper,
  notifications_active: NotificationsActive,
  notifications_off: NotificationsOff,
} satisfies Record<string, ComponentType<SVGProps<SVGSVGElement>>>

export type IconName = keyof typeof ICONS

interface IconProps extends Omit<SVGProps<SVGSVGElement>, 'name'> {
  name: IconName
  size?: number
  /** Accessible name; omit for decorative icons. */
  label?: string
}

export function Icon({ name, size = 24, label, className, ...rest }: IconProps) {
  const Svg = ICONS[name]

  return (
    <Svg
      width={size}
      height={size}
      fill="currentColor"
      className={cn('inline-block shrink-0', className)}
      aria-hidden={label ? undefined : true}
      aria-label={label}
      role={label ? 'img' : undefined}
      focusable="false"
      {...rest}
    />
  )
}
