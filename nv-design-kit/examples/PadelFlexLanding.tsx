import {
  Hero,
  IconColumns,
  Feature,
  ComparisonGrid,
  QuantityBreaks,
  ReviewWall,
  Guarantee,
  StatsCounter,
  Faq,
  CtaBlock,
} from '../src/index';

/**
 * Sample PadelFlex™ product landing page, composed entirely from NV Design Kit
 * sections. Shows the intended usage: stack whole sections top-to-bottom, set
 * props, and let the shared stylesheet do the styling. Demonstrates the mixed
 * headline style (`headlineStyle="mixed"` + *asterisk* accents).
 */
export function PadelFlexLanding() {
  return (
    <main className="nv-demo-page">
      <Hero
        eyebrow="PADELFLEX™ SULOR"
        headline="Mindre skador. *Mer spel.*"
        highlight=""
        headlineStyle="mixed"
        subheadline="Padelanpassade innersulor som ger riktad stötdämpning och förstärkt sidostabilitet – matchning efter matchning."
        ratingText="4,8/5 · 9 000+ nöjda padelspelare"
        bullets={['Riktad stötdämpning', 'Förstärkt sidostabilitet', 'Kliniskt utprovad']}
        ctaText="Köp nu – 499 kr"
        cta2Text="Läs mer"
        guarantee="60 dagars nöjd-kund-garanti"
      />

      <section className="nv-demo-band">
        <IconColumns />
      </section>

      <section className="nv-demo-band nv-demo-band--alt">
        <Feature
          layout="split"
          imageSide="right"
          eyebrow="FUNKTION"
          headline="Byggd för *padelns* sidorörelser."
          headlineStyle="mixed"
          text="Generiska multisportsulor tar inte hänsyn till padelns snabba sidled. PadelFlex™ är formad efter belastningen där du faktiskt spelar."
          bullets={[
            { text: 'Riktad stötdämpning', desc: 'Där hälen och trampdynan tar smällen' },
            { text: 'Förstärkt sidostabilitet', desc: 'Håller foten stadig i utfallen' },
            { text: 'Padelanpassad biomekanik', desc: 'Utvecklad med spelare' },
          ]}
          ctaText="Se hur det fungerar"
        />
      </section>

      <section className="nv-demo-band">
        <ComparisonGrid
          headline="Varför spelare *byter* till PadelFlex™"
          headlineStyle="mixed"
        />
      </section>

      <section className="nv-demo-band nv-demo-band--offer">
        <QuantityBreaks heading="Välj ditt paket" headlineStyle="mixed" />
      </section>

      <section className="nv-demo-band">
        <ReviewWall
          headline1="Därför älskar spelare"
          headlineAccent="PadelFlex™"
          headlineStyle="mixed"
          autoScroll={false}
          columns="4"
        />
      </section>

      <section className="nv-demo-band nv-demo-band--alt">
        <StatsCounter />
      </section>

      <section className="nv-demo-band">
        <Guarantee headlineStyle="mixed" />
      </section>

      <section className="nv-demo-band nv-demo-band--alt">
        <Faq title="Vanliga frågor" />
      </section>

      <section className="nv-demo-band nv-demo-band--cta">
        <CtaBlock
          eyebrow="REDO ATT SPELA SMARTARE?"
          headline="Ge fötterna det stöd de förtjänar"
          description="Fri frakt i Norden · 60 dagars öppet köp · Över 9 000 nöjda spelare."
          primaryText="Köp PadelFlex™ – 499 kr"
          secondaryText="Se alla paket"
        />
      </section>
    </main>
  );
}
