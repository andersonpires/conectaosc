/**
 * Converte markdown básico para HTML (quando a IA retorna markdown em vez de HTML).
 * Se o texto já for HTML (contém <p>, <strong>, etc.), retorna como está.
 */
export function markdownToHtml(text) {
  if (!text || typeof text !== 'string') return '';
  if (/<\/(p|strong|em|br|div|span)>|<(p|strong|em|br|div|span)[\s>]/i.test(text)) {
    return text;
  }
  let html = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
  html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  html = html.replace(/\*([^*]+?)\*/g, '<em>$1</em>');
  html = html.replace(/\n\n+/g, '</p><p>');
  html = html.replace(/\n/g, '<br>');
  if (html && !html.startsWith('<')) html = '<p>' + html + '</p>';
  return html;
}
