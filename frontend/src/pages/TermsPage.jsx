import { legalContent } from '../content/legalContent';
import { seoContent } from '../content/seoContent';
import LegalPage from './LegalPage';

export default function TermsPage() {
  const { title, lastUpdated, sections } = legalContent.terms;

  return (
    <LegalPage
      title={title}
      lastUpdated={lastUpdated}
      sections={sections}
      seo={seoContent.terms}
    />
  );
}
