/** Click-to-chat links: work from any phone with WhatsApp, no API account needed. */
export function waLink(phone: string | null | undefined, text?: string): string | null {
  if (!phone) return null
  const digits = phone.replace(/\D/g, '')
  if (!digits) return null
  return `https://wa.me/${digits}` + (text ? `?text=${encodeURIComponent(text)}` : '')
}

export function telLink(phone: string | null | undefined): string | null {
  if (!phone) return null
  const digits = phone.replace(/\D/g, '')
  return digits ? `tel:+${digits}` : null
}
