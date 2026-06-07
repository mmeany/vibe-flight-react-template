import { Helmet } from 'react-helmet-async';

export default function SeoHead({ title, description, canonical }) {
  return (
    <Helmet>
      <title>{title}</title>
      {description && <meta name="description" content={description} />}
      {canonical && <link rel="canonical" href={canonical} />}
    </Helmet>
  );
}
