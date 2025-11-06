export interface Category {
  id: number;
  name: string;
  slug: string;
}

export interface ProductVariant {
  id: number;
  name?: string | null;
  sku: string;
  price: number;
  is_active: boolean;
  stock_quantity: number;
  image_url?: string | null;
}

export interface ProductAttributeValue {
  id: number;
  value: string;
  attribute: {
    id: number;
    name: string;
    slug: string;
  };
}

export interface ApiProduct {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  short_description?: string | null;
  price: number;
  compare_at_price?: number | null;
  is_active: boolean;
  categories?: Category[];
  primary_variant?: ProductVariant | null;
  attribute_values?: ProductAttributeValue[];
  image_url?: string | null;
}
