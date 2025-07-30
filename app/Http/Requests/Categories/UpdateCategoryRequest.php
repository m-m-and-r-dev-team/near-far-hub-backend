<?php

declare(strict_types=1);

namespace App\Http\Requests\Categories;

use App\Http\DataTransferObjects\Categories\UpdateCategoryData;
use App\Libraries\Helpers\Rules\ValidationRuleHelper;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class UpdateCategoryRequest extends FormRequest
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
                ValidationRuleHelper::SOMETIMES,
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
    public function dto(): UpdateCategoryData
    {
        return new UpdateCategoryData([
            UpdateCategoryData::NAME => $this->input(self::NAME),
            UpdateCategoryData::DESCRIPTION => $this->input(self::DESCRIPTION),
            UpdateCategoryData::PARENT_ID => $this->input(self::PARENT_ID),
            UpdateCategoryData::ICON => $this->input(self::ICON),
            UpdateCategoryData::COLOR => $this->input(self::COLOR),
            UpdateCategoryData::SORT_ORDER => $this->input(self::SORT_ORDER),
            UpdateCategoryData::IS_ACTIVE => $this->input(self::IS_ACTIVE) !== null ? $this->boolean(self::IS_ACTIVE) : null,
            UpdateCategoryData::IS_FEATURED => $this->input(self::IS_FEATURED) !== null ? $this->boolean(self::IS_FEATURED) : null,
            UpdateCategoryData::META_TITLE => $this->input(self::META_TITLE),
            UpdateCategoryData::META_DESCRIPTION => $this->input(self::META_DESCRIPTION),
            UpdateCategoryData::ATTRIBUTES => $this->input(self::ATTRIBUTES),
            UpdateCategoryData::VALIDATION_RULES => $this->input(self::VALIDATION_RULES),
        ]);
    }
}