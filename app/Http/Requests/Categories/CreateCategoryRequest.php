<?php

declare(strict_types=1);

namespace App\Http\Requests\Categories;

use App\Http\DataTransferObjects\Categories\CreateCategoryData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class CreateCategoryRequest extends FormRequest
{
    private const NAME = 'name';
    private const DESCRIPTION = 'description';
    private const PARENT_ID = 'parent_id';
    private const ICON = 'icon';
    private const COLOR = 'color';
    private const SORT_ORDER = 'sort_order';
    private const IS_ACTIVE = 'is_active';
    private const IS_FEATURED = 'is_featured';
    private const META_TITLE = 'meta_title';
    private const META_DESCRIPTION = 'meta_description';
    private const ATTRIBUTES = 'attributes';
    private const VALIDATION_RULES = 'validation_rules';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            self::NAME => [
                ValidationRuleHelper::REQUIRED,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::min(2),
                ValidationRuleHelper::max(100)
            ],
            self::DESCRIPTION => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(1000)
            ],
            self::PARENT_ID => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::INTEGER,
                'exists:categories,id'
            ],
            self::ICON => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(50)
            ],
            self::COLOR => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                'regex:/^#[a-fA-F0-9]{6}$/'
            ],
            self::SORT_ORDER => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::INTEGER,
                'min:0'
            ],
            self::IS_ACTIVE => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN
            ],
            self::IS_FEATURED => [
                ValidationRuleHelper::SOMETIMES,
                ValidationRuleHelper::BOOLEAN
            ],
            self::META_TITLE => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(60)
            ],
            self::META_DESCRIPTION => [
                ValidationRuleHelper::NULLABLE,
                ValidationRuleHelper::STRING,
                ValidationRuleHelper::max(160)
            ],
            self::ATTRIBUTES => [
                ValidationRuleHelper::NULLABLE,
                'array'
            ],
            self::VALIDATION_RULES => [
                ValidationRuleHelper::NULLABLE,
                'array'
            ]
        ];
    }

    /**
     * @throws UnknownProperties
     */
    public function dto(): CreateCategoryData
    {
        return new CreateCategoryData([
            CreateCategoryData::NAME => $this->input(self::NAME),
            CreateCategoryData::DESCRIPTION => $this->input(self::DESCRIPTION),
            CreateCategoryData::PARENT_ID => $this->input(self::PARENT_ID),
            CreateCategoryData::ICON => $this->input(self::ICON),
            CreateCategoryData::COLOR => $this->input(self::COLOR),
            CreateCategoryData::SORT_ORDER => $this->input(self::SORT_ORDER, 0),
            CreateCategoryData::IS_ACTIVE => $this->boolean(self::IS_ACTIVE, true),
            CreateCategoryData::IS_FEATURED => $this->boolean(self::IS_FEATURED, false),
            CreateCategoryData::META_TITLE => $this->input(self::META_TITLE),
            CreateCategoryData::META_DESCRIPTION => $this->input(self::META_DESCRIPTION),
            CreateCategoryData::ATTRIBUTES => $this->input(self::ATTRIBUTES, []),
            CreateCategoryData::VALIDATION_RULES => $this->input(self::VALIDATION_RULES, []),
        ]);
    }
}