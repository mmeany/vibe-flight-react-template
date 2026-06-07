import { legalContent } from '../content/legalContent';
import { seoContent } from '../content/seoContent';
import LegalPage from './LegalPage';

export default function PrivacyPolicyPage() {
  const { title, lastUpdated, sections } = legalContent.privacy;

  return (
    <LegalPage
      title={title}
      lastUpdated={lastUpdated}
      sections={sections}
      seo={seoContent.privacy}
    />
  );
}
