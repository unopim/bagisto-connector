<?php

namespace Webkul\TVCMall\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductAttributeMappingSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tvc_mall_product_attribute_mappings')->delete();

        DB::table('tvc_mall_product_attribute_mappings')->insert([
            [
                'id' => 1,
                'unopim_code' => 'sku',
                'tvc_mall_code' => 'Sku',
            ], [
                'id' => 2,
                'unopim_code' => 'name',
                'tvc_mall_code' => 'Title',
            ], [
                'id' => 3,
                'unopim_code' => 'url_key',
                'tvc_mall_code' => 'Sku',
            ], [
                'id' => 5,
                'unopin_code' => 'brandName',
                'tvc_mall_code' => 'Properties.Brand',
            ], [
                'id' => 6,
                'unopim_code' => 'description',
                'tvc_mall_code' => 'Description',
            ], [
                'id' => 7,
                'unopim_code' => 'short_description',
                'tvc_mall_code' => 'Description',
            ], [
                'id' => 8,
                'unopim_code' => 'meta_title',
                'tvc_mall_code' => 'ProductSEO.SeoTitle',
            ], [
                'id' => 9,
                'unopim_code' => 'meta_keywords',
                'tvc_mall_code' => 'ProductSEO.Keyword',
            ], [
                'id' => 10,
                'unopim_code' => 'meta_description',
                'tvc_mall_code' => 'ProductSEO.SeoDescription',
            ], [
                'id' => 11,
                'unopim_code' => 'price',
                'tvc_mall_code' => 'Price',
            ], [
                'id' => 12,
                'unopim_code' => 'cost',
                'tvc_mall_code' => 'DiscountedPrice',
            ], [
                'id' => 13,
                'unopim_code' => 'product_status',
                'tvc_mall_code' => 'ProductStatus',
            ], [
                'id' => 14,
                'unopim_code' => 'color_style',
                'tvc_mall_code' => 'Properties.ColorStyle',
            ], [
                'id' => 15,
                'unopim_code' => 'unitWidth',
                'tvc_mall_code' => 'Width',
            ], [
                'id' => 16,
                'unopim_code' => 'packWidth',
                'tvc_mall_code' => 'Width',
            ], [
                'id' => 17,
                'unopim_code' => 'unitHeight',
                'tvc_mall_code' => 'Height',
            ], [
                'id' => 18,
                'unopim_code' => 'product_packaging',
                'tvc_mall_code' => 'Properties.Retail Packaging',
            ], [
                'id' => 19,
                'unopim_code' => 'user_manual_language',
                'tvc_mall_code' => 'Properties.User Manual (Language)',
            ], [
                'id' => 20,
                'unopim_code' => 'unitWeight',
                'tvc_mall_code' => 'GrossWeight',
            ], [
                'id' => 21,
                'unopim_code' => 'packWeight',
                'tvc_mall_code' => 'VolumeWeight',
            ], [
                'id' => 22,
                'unopim_code' => 'authorization_image',
                'tvc_mall_code' => 'AuthorizationImage',
            ], [
                'id' => 23,
                'unopim_code' => 'leadTimeLevel',
                'tvc_mall_code' => 'LeadTime',
            ], [
                'id' => 24,
                'unopim_code' => 'length',
                'tvc_mall_code' => 'Length',
            ], [
                'id' => 25,
                'unopim_code' => 'unitLength',
                'tvc_mall_code' => 'Length',
            ], [
                'id' => 26,
                'unopim_code' => 'priceList',
                'tvc_mall_code' => 'PriceList',
            ], [
                'id' => 27,
                'unopim_code' => 'compatible_list',
                'tvc_mall_code' => 'CompatibleList',
            ], [
                'id' => 28,
                'unopim_code' => 'package_list',
                'tvc_mall_code' => 'PackageList',
            ], [
                'id' => 29,
                'unopim_code' => 'same_model_different_color',
                'tvc_mall_code' => 'Spu.Items',
            ], [
                'id' => 30,
                'unopim_code' => 'same_color_different_model',
                'tvc_mall_code' => 'Applicables',
            ], [
                'id' => 31,
                'unopim_code' => 'moq',
                'tvc_mall_code' => 'MinimumOrderQuantity',
            ], [
                'id' => 32,
                'unopim_code' => 'gmtModified',
                'tvc_mall_code' => 'ModifiedOn',
            ], [
                'id' => 33,
                'unopim_code' => 'gmtListed',
                'tvc_mall_code' => 'PublishDate',
            ], [
                'id' => 34,
                'unopim_code' => 'warehouse',
                'tvc_mall_code' => 'Warehouse',
            ], [
                'id' => 35,
                'unopim_code' => 'color',
                'tvc_mall_code' => 'Spu.CurrentItem.Attributes.Color',
            ], [
                'id' => 36,
                'unopim_code' => 'status',
                'tvc_mall_code' => 'ProductStatus',
            ], [
                'id' => 37,
                'unopim_code' => 'source',
                'tvc_mall_code' => 'Source',
            ], [
                'id' => 38,
                'unopim_code' => 'CaseFeature',
                'tvc_mall_code' => 'Properties.Case Feature',
            ], [
                'id' => 39,
                'unopim_code' => 'ColorStyle',
                'tvc_mall_code' => 'Properties.ColorStyle',
            ], [
                'id' => 40,
                'unopim_code' => 'Material',
                'tvc_mall_code' => 'Properties.Material',
            ], [
                'id' => 41,
                'unopim_code' => 'SurfaceWorkmanship',
                'tvc_mall_code' => 'Properties.Surface Workmanship',
            ], [
                'id' => 42,
                'unopim_code' => 'GrossWeight',
                'tvc_mall_code' => 'Properties.Gross Weight',
            ], [
                'id' => 43,
                'unopim_code' => 'VolumeWeight',
                'tvc_mall_code' => 'Properties.Volume Weight',
            ], [
                'id' => 44,
                'unopim_code' => 'packLength',
                'tvc_mall_code' => 'Properties.Length',
            ], [
                'id' => 45,
                'unopim_code' => 'packWidth',
                'tvc_mall_code' => 'Properties.Width',
            ], [
                'id' => 46,
                'unopim_code' => 'packHeight',
                'tvc_mall_code' => 'Properties.Height',
            ], [
                'id' => 47,
                'unopim_code' => 'packWeight',
                'tvc_mall_code' => 'Properties.Weight',
            ], [
                'id' => 48,
                'unopim_code' => 'ProductPackaging',
                'tvc_mall_code' => 'Properties.Retail Packaging',
            ], [
                'id' => 49,
                'unopim_code' => 'SpecificationEAN',
                'tvc_mall_code' => 'EAN',
            ], [
                'id' => 50,
                'unopim_code' => 'WithRetailPackaging',
                'tvc_mall_code' => 'Properties.With Retail Packaging',
            ], [
                'id' => 51,
                'unopim_code' => 'CompatibleList',
                'tvc_mall_code' => 'CompatibleList',
            ], [
                'id' => 52,
                'unopim_code' => 'PackageList',
                'tvc_mall_code' => 'PackageList',
            ], [
                'id' => 53,
                'unopim_code' => 'LeadTimeLevel',
                'tvc_mall_code' => 'LeadTime',
            ], [
                'id' => 54,
                'unopim_code' => 'gallery',
                'tvc_mall_code' => 'Images.ProductImages',
            ], [
                'id' => 55,
                'unopim_code' => 'additional_images',
                'tvc_mall_code' => 'Images.SpecialImages',
            ]
        ]);
    }
}
