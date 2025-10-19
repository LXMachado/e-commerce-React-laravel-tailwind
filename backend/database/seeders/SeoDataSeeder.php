<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class SeoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedCategoriesSeo();
        $this->seedProductsSeo();
    }

    /**
     * Seed SEO data for categories
     */
    private function seedCategoriesSeo(): void
    {
        $categories = Category::all();

        foreach ($categories as $category) {
            $seoData = $this->getCategorySeoData($category->name, $category->description);

            $category->update([
                'seo_title' => $seoData['title'],
                'seo_description' => $seoData['description'],
            ]);
        }

        $this->command->info('Categories SEO data seeded successfully');
    }

    /**
     * Seed SEO data for products
     */
    private function seedProductsSeo(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            $seoData = $this->getProductSeoData($product->name, $product->description, $product->categories);

            $product->update([
                'seo_title' => $seoData['title'],
                'seo_description' => $seoData['description'],
            ]);
        }

        $this->command->info('Products SEO data seeded successfully');
    }

    /**
     * Get SEO data for a category
     */
    private function getCategorySeoData(string $categoryName, ?string $description): array
    {
        $baseTitle = $categoryName . ' - Weekender Solar';
        $baseDescription = $description ?: "Discover premium {$categoryName} products at Weekender Solar. High-quality, sustainable solar-powered solutions for your energy needs.";

        return [
            'title' => $baseTitle,
            'description' => $baseDescription,
        ];
    }

    /**
     * Get SEO data for a product
     */
    private function getProductSeoData(string $productName, ?string $description, $categories): array
    {
        $categoryNames = $categories->pluck('name')->join(', ');
        $baseTitle = $productName . ' - ' . $categoryNames . ' | Weekender Solar';

        $baseDescription = $description ?: "Premium {$productName} available at Weekender Solar. High-quality solar-powered {$categoryNames} for sustainable energy solutions.";

        return [
            'title' => $baseTitle,
            'description' => $baseDescription,
        ];
    }
}
