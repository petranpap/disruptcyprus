import { Suspense, lazy, type ReactNode } from 'react'
import { Navigate, createBrowserRouter, type RouteObject } from 'react-router'
import { ProfilePage } from '@/features/profile/ProfilePage'
import { NotFoundPage } from '@/features/shell/NotFoundPage'
import { PlaceholderPage } from '@/features/shell/PlaceholderPage'
import { RouteError } from '@/features/shell/RouteError'
import { GuestOnly, RequireAuth, Splash } from './guards'
import { AppShell, AuthLayout, OnboardingLayout } from './layouts'

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
          { index: true, element: <PlaceholderPage titleKey="tabs.forYou" icon="home" /> },
          { path: '/news', element: <PlaceholderPage titleKey="tabs.news" /> },
          { path: '/startups', element: <PlaceholderPage titleKey="tabs.startups" /> },
          { path: '/research', element: <PlaceholderPage titleKey="tabs.research" /> },
          { path: '/investors', element: <PlaceholderPage titleKey="tabs.investors" /> },
          { path: '/events', element: <PlaceholderPage titleKey="tabs.events" icon="calendar_month" /> },
          { path: '/explore', element: <PlaceholderPage titleKey="nav.explore" icon="explore" /> },
          { path: '/saved', element: <PlaceholderPage titleKey="nav.saved" icon="bookmark" /> },
          { path: '/notifications', element: <PlaceholderPage titleKey="nav.notifications" icon="notifications" /> },
          { path: '/profile', element: <ProfilePage /> },
          { path: '/articles/:slug', element: <PlaceholderPage titleKey="tabs.news" /> },
          { path: '/events/:slug', element: <PlaceholderPage titleKey="tabs.events" icon="calendar_month" /> },
          { path: '/digests/:slug', element: <PlaceholderPage titleKey="tabs.news" /> },
        ],
      },
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
