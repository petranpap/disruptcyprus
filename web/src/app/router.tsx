import { Suspense, lazy, type ComponentType, type ReactNode } from 'react'
import { Navigate, createBrowserRouter, type RouteObject } from 'react-router'
import { HomePage } from '@/features/feed/HomePage'
import { SectionPage } from '@/features/feed/SectionPage'
import { ProfilePage } from '@/features/profile/ProfilePage'
import { NotFoundPage } from '@/features/shell/NotFoundPage'
import { RouteError } from '@/features/shell/RouteError'
import { GuestOnly, RequireAuth, Splash } from './guards'
import { AppShell, AuthLayout, OnboardingLayout } from './layouts'
import { RootLayout } from './RootLayout'

/** Route-level code splitting: first-run and onboarding screens load only when visited. */
const WelcomePage = lazy(() =>
  import('@/features/auth/WelcomePage').then((module) => ({ default: module.WelcomePage })),
)
const SignInPage = lazy(() => import('@/features/auth/SignInPage').then((module) => ({ default: module.SignInPage })))
const SignUpPage = lazy(() => import('@/features/auth/SignUpPage').then((module) => ({ default: module.SignUpPage })))
const ForgotPasswordPage = lazy(() =>
  import('@/features/auth/ForgotPasswordPage').then((module) => ({ default: module.ForgotPasswordPage })),
)
const ResetPasswordPage = lazy(() =>
  import('@/features/auth/ResetPasswordPage').then((module) => ({ default: module.ResetPasswordPage })),
)
const AccountStep = lazy(() =>
  import('@/features/onboarding/AccountStep').then((module) => ({ default: module.AccountStep })),
)
const IndustriesStep = lazy(() =>
  import('@/features/onboarding/IndustriesStep').then((module) => ({ default: module.IndustriesStep })),
)
const lazyNamed = <K extends string, T extends Record<K, ComponentType>>(load: () => Promise<T>, name: K) =>
  lazy(() => load().then((module) => ({ default: module[name] })))
const ArticlePage = lazyNamed(() => import('@/features/reader/ArticlePage'), 'ArticlePage')
const EventPage = lazyNamed(() => import('@/features/events/EventPage'), 'EventPage')
const EventsPage = lazyNamed(() => import('@/features/events/EventsPage'), 'EventsPage')
const DigestPage = lazyNamed(() => import('@/features/digests/DigestView'), 'DigestPage')
const ExplorePage = lazyNamed(() => import('@/features/explore/ExplorePage'), 'ExplorePage')
const IndustryPage = lazyNamed(() => import('@/features/explore/IndustryPage'), 'IndustryPage')
const SavedPage = lazyNamed(() => import('@/features/saved/SavedPage'), 'SavedPage')
const IndustriesSettings = lazyNamed(() => import('@/features/settings/IndustriesSettings'), 'IndustriesSettings')
const NotificationsPage = lazyNamed(() => import('@/features/notifications/NotificationsPage'), 'NotificationsPage')
const NotificationSettings = lazyNamed(() => import('@/features/settings/NotificationSettings'), 'NotificationSettings')
const LanguageSettings = lazyNamed(() => import('@/features/settings/LanguageSettings'), 'LanguageSettings')
const AppearanceSettings = lazyNamed(() => import('@/features/settings/AppearanceSettings'), 'AppearanceSettings')
const AccountSettings = lazyNamed(() => import('@/features/settings/AccountSettings'), 'AccountSettings')
const PrivacySettings = lazyNamed(() => import('@/features/settings/PrivacySettings'), 'PrivacySettings')
const NotificationsStep = lazy(() =>
  import('@/features/onboarding/NotificationsStep').then((module) => ({ default: module.NotificationsStep })),
)

const page = (element: ReactNode) => <Suspense fallback={<Splash />}>{element}</Suspense>

/** The component gallery exists only in development builds (dead-code eliminated in production). */
const devRoutes: RouteObject[] = import.meta.env.DEV
  ? [
      {
        path: '/dev/components',
        Component: lazy(() =>
          import('@/features/dev/ComponentsPage').then((module) => ({ default: module.ComponentsPage })),
        ),
        hydrateFallbackElement: <Splash />,
      },
    ]
  : []

export const routes: RouteObject[] = [
  {
    element: <RootLayout />,
    errorElement: <RouteError />,
    children: [
      {
        path: '/welcome',
        element: <GuestOnly>{page(<WelcomePage />)}</GuestOnly>,
      },
      {
        element: (
          <GuestOnly>
            <AuthLayout />
          </GuestOnly>
        ),
        children: [
          { path: '/sign-in', element: page(<SignInPage />) },
          { path: '/sign-up', element: page(<SignUpPage />) },
          { path: '/forgot-password', element: page(<ForgotPasswordPage />) },
          { path: '/reset-password', element: page(<ResetPasswordPage />) },
        ],
      },
      {
        path: '/onboarding',
        element: (
          <RequireAuth>
            <OnboardingLayout />
          </RequireAuth>
        ),
        children: [
          { index: true, element: <Navigate to="account" replace /> },
          { path: 'account', element: page(<AccountStep />) },
          { path: 'industries', element: page(<IndustriesStep />) },
          { path: 'notifications', element: page(<NotificationsStep />) },
        ],
      },
      {
        element: <AppShell />,
        children: [
          { index: true, element: <HomePage /> },
          { path: '/news', element: <SectionPage section="news" /> },
          { path: '/startups', element: <SectionPage section="startups" /> },
          { path: '/research', element: <SectionPage section="research" /> },
          { path: '/investors', element: <SectionPage section="investors" /> },
          { path: '/events', element: page(<EventsPage />) },
          { path: '/explore', element: page(<ExplorePage />) },
          { path: '/explore/:industry', element: page(<IndustryPage />) },
          { path: '/saved', element: page(<SavedPage />) },
          { path: '/digests/:slug', element: page(<DigestPage />) },
          { path: '/notifications', element: page(<NotificationsPage />) },
          { path: '/profile', element: <ProfilePage /> },
          { path: '/settings/language', element: page(<LanguageSettings />) },
          { path: '/settings/appearance', element: page(<AppearanceSettings />) },
          { path: '/settings/industries', element: <RequireAuth>{page(<IndustriesSettings />)}</RequireAuth> },
          { path: '/settings/notifications', element: <RequireAuth>{page(<NotificationSettings />)}</RequireAuth> },
          { path: '/settings/account', element: <RequireAuth>{page(<AccountSettings />)}</RequireAuth> },
          { path: '/settings/privacy', element: <RequireAuth>{page(<PrivacySettings />)}</RequireAuth> },
          { path: '/settings', element: <Navigate to="/profile" replace /> },
        ],
      },
      { path: '/articles/:slug', element: page(<ArticlePage />) },
      { path: '/events/:slug', element: page(<EventPage />) },
      ...devRoutes.map((route) => ({
        ...route,
        element: page(route.Component ? <route.Component /> : null),
        Component: undefined,
      })),
      { path: '*', element: <NotFoundPage /> },
    ],
  },
]

export function createRouter() {
  return createBrowserRouter(routes)
}
