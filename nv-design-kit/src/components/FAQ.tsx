/** One question/answer pair in the accordion. */
export interface FaqItem {
  /** The question shown in the summary row. */
  question: string;
  /** The answer revealed when the item is open. */
  answer?: string;
}

export interface FAQProps {
  /** Section title above the accordion. */
  title?: string;
  /** The FAQ items. The first item renders open, matching the PHP. */
  items?: FaqItem[];
}

/**
 * NV: FAQ — a native `<details>` accordion of questions and answers with an
 * optional section title; the first item is open by default. Mirrors the
 * `nv-faq` Elementor widget.
 */
export function FAQ({
  title = 'Vanliga frågor',
  items = [
    { question: 'Hur snabbt ser jag resultat?', answer: 'De flesta märker en skillnad inom de första två veckorna vid daglig användning.' },
    { question: 'Har ni fri frakt?', answer: 'Ja, vi erbjuder fri frakt på alla beställningar över 500 kr.' },
    { question: 'Vad är er returpolicy?', answer: 'Du har 60 dagars öppet köp med nöjd-kund-garanti.' },
  ],
}: FAQProps) {
  if (items.length === 0) return null;

  return (
    <div className="nv-pw-faq">
      {title !== '' && <h3 className="nv-pw-faq__title">{title}</h3>}
      <div className="nv-pw-faq__list">
        {items.map((item, index) =>
          (item.question ?? '') !== '' ? (
            <details className="nv-pw-faq__item" open={index === 0} key={index}>
              <summary className="nv-pw-faq__question">{item.question}</summary>
              <div className="nv-pw-faq__answer">{item.answer}</div>
            </details>
          ) : null,
        )}
      </div>
    </div>
  );
}
