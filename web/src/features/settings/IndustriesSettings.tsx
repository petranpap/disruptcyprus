import { useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import type { MyIndustries } from '@/api/schemas'
import { useIndustryList } from '@/api/content'
import { useMyIndustries, useUpdateMyIndustries } from '@/api/taxonomy'
import { Button } from '@/components/ui/Button'
import { Skeleton } from '@/components/ui/Skeleton'
import { Switch } from '@/components/ui/Switch'
import { GROUP_ORDER } from '@/features/onboarding/constants'
import { TopicTile } from '@/features/onboarding/TopicTile'
import { useUiStore } from '@/stores/ui'
import { upper } from '@/lib/greek'
import { SettingsCard, SettingsPage } from './SettingsLayout'

function IndustriesForm({ initial }: { initial: MyIndustries }) {
  const { t, i18n } = useTranslation()
  const industries = useIndustryList(i18n.language)
  const update = useUpdateMyIndustries()
  const showToast = useUiStore((state) => state.showToast)
  const [selected, setSelected] = useState(() => new Set(initial.industry_ids))
  const [notify, setNotify] = useState(() => new Set(initial.notify_ids))

  const followed = useMemo(
    () => (industries.data ?? []).filter((industry) => selected.has(industry.id)),
    [industries.data, selected],
  )

  const save = () =>
    update.mutate(
      { industry_ids: [...selected], notify_ids: [...notify].filter((id) => selected.has(id)) },
      { onSuccess: () => showToast(t('settings.saved')), onError: () => showToast(t('errors.generic')) },
    )

  return (
    <>
      {followed.length > 0 && (
        <SettingsCard title={t('settings.industries.alertsTitle')}>
          <p className="mb-2 text-body-sm text-on-surface-variant">{t('settings.industries.alertsHint')}</p>
          <div className="divide-y divide-hairline">
            {followed.map((industry) => (
              <Switch
                key={industry.id}
                label={industry.name}
                checked={notify.has(industry.id)}
                onChange={(checked) =>
                  setNotify((current) => {
                    const next = new Set(current)
                    if (checked) next.add(industry.id)
                    else next.delete(industry.id)
                    return next
                  })
                }
              />
            ))}
          </div>
        </SettingsCard>
      )}

      {industries.isPending ? (
        <Skeleton className="h-64 rounded-card" />
      ) : (
        GROUP_ORDER.map((group) => (
          <section key={group} className="space-y-3">
            <h2 className="text-label-md tracking-wider text-on-surface-variant">
              {upper(t(`onboarding.industries.groups.${group}`))}
            </h2>
            <div className="grid grid-cols-2 gap-3.5 sm:grid-cols-3">
              {(industries.data ?? [])
                .filter((industry) => industry.group === group)
                .map((industry) => (
                  <TopicTile
                    key={industry.id}
                    industry={industry}
                    selected={selected.has(industry.id)}
                    onToggle={(item) =>
                      setSelected((current) => {
                        const next = new Set(current)
                        if (next.has(item.id)) next.delete(item.id)
                        else next.add(item.id)
                        return next
                      })
                    }
                  />
                ))}
            </div>
          </section>
        ))
      )}

      <div className="sticky bottom-24 lg:bottom-6">
        <Button onClick={save} loading={update.isPending} className="shadow-float">
          {t('common.save')}
        </Button>
      </div>
    </>
  )
}

export function IndustriesSettings() {
  const { t } = useTranslation()
  const mine = useMyIndustries()

  return (
    <SettingsPage title={t('settings.industries.title')} description={t('onboarding.industries.subtitle')}>
      {mine.isPending ? (
        <Skeleton className="h-64 rounded-card" />
      ) : (
        <IndustriesForm initial={mine.data ?? { industry_ids: [], notify_ids: [] }} />
      )}
    </SettingsPage>
  )
}
