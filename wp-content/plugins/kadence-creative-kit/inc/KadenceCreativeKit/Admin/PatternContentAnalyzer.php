<?php

namespace KadenceWP\CreativeKit\Admin;

class PatternContentAnalyzer
{

    private $requiredContent = [];
    private $patterns;

    public function __construct(array $patterns)
    {
        $this->patterns = $patterns;
        $this->analyzeAllContent();
    }

    private function stripStringRender($string)
    {
        return strtolower(preg_replace('/[^0-9a-z-]/', '', $string));
    }

    private function normalize_pattern_type($type)
    {
        return strtoupper( str_replace('-', '_', $type) );
    }
    private function analyzeAllContent()
    {
        foreach ($this->patterns as $key => $patternData) {
            $pattern_type_id = $this->normalize_pattern_type( array_key_first( $patternData['categories'] ) );

            $this->requiredContent[ $pattern_type_id . '_' . $key ] = [
                'id' => $pattern_type_id,
                'fields' => $this->analyzeContent($patternData)
            ];
        }
    }

    public function getRequiredContent()
    {
        return $this->requiredContent;
    }

    private function analyzeContent($patternData)
    {
        $requierdInfo = [];

        if (empty($patternData['html']) || empty($patternData['categories']) || empty($patternData['id'])) {
            return $requierdInfo;
        }

        $content = $patternData['html'];
        $category = array_key_first($patternData['categories']);

        $requierdInfo = $this->getFields($content, $category);

        return $requierdInfo;
    }

    /**
     * Analyzes content to determine what AI content structure is needed
     *
     * @param string $content The content to analyze
     * @param string $category The content category (columns, text, hero, etc.)
     * @return array The expected AI content structure
     */
    public function getFields(string $content, string $category): array
    {
        $expectedStructure = [];

        switch ($category) {
            case 'columns':
                // Check for headline fields
                $headingFields = [];
                if (str_contains($content, 'Write a short headline')) {
                    $headingFields[] = ['name' => 'short'];
                }
                if (str_contains($content, 'Compose a captivating title for this section.')) {
                    $headingFields[] = ['name' => 'medium'];
                }
                if (!empty($headingFields)) {
                    $expectedStructure[] = [
                        'name' => 'heading',
                        'fields' => $headingFields
                    ];
                }

                // Check for sentence fields
                $sentenceFields = [];
                if (str_contains($content, 'Support your idea with a clear, descriptive sentence or phrase that has a consistent writing style.')) {
                    $sentenceFields[] = ['name' => 'short'];
                }
                if (!empty($sentenceFields)) {
                    $expectedStructure[] = [
                        'name' => 'sentence',
                        'fields' => $sentenceFields
                    ];
                }

                // Column specific content
                if (str_contains($content, 'Add a descriptive title for the column.')) {
                    $columnFields = [];
                    if (str_contains($content, 'Add a descriptive title for the column.')) {
                        $columnFields[] = ['name' => 'title-medium'];
                    }
                    if (str_contains($content, 'Add context to your column. Help visitors understand the value they can get from your products and services.')) {
                        $columnFields[] = ['name' => 'sentence-short'];
                    }

                    $count = substr_count($content, 'Add a descriptive title for the column.');
                    $columnStruct = [
                        'name' => 'columns',
                        'fields' => $columnFields
                    ];
                    if ($count > 1) {
                        $columnStruct['repeat'] = $count;
                    }
                    $expectedStructure[] = $columnStruct;
                }
                break;

            case 'featured-products':
            case 'featured-product':
                // Check for heading fields
                $headingFields = [];
                if (str_contains($content, 'An engaging product or feature headline here')) {
                    $headingFields[] = ['name' => 'medium'];
                }
                if (!empty($headingFields)) {
                    $expectedStructure[] = [
                        'name' => 'heading',
                        'fields' => $headingFields
                    ];
                }

                // Check for sentence
                if (str_contains($content, 'Write a short descriptive paragraph about your product.')) {
                    $expectedStructure[] = [
                        'name' => 'sentence',
                        'fields' => [['name' => 'medium']]
                    ];
                }

                // Check for price
                if (str_contains($content, '$19.99')) {
                    $expectedStructure[] = [
                        'name' => 'price',
                        'fields' => [['name' => 'price']]
                    ];
                }

                // Check for button
                if (str_contains($content, 'Call To Action') || str_contains($content, 'Call to Action')) {
                    $expectedStructure[] = [
                        'name' => 'button',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for product features
                $featureCount = substr_count($content, 'Short feature description') +
                    substr_count($content, 'Another short feature description');
                if ($featureCount > 0) {
                    $featureStruct = [
                        'name' => 'product-features-and-benefits',
                        'fields' => [['name' => 'list-item-short']]
                    ];
                    if ($featureCount > 1) {
                        $featureStruct['repeat'] = $featureCount;
                    }
                    $expectedStructure[] = $featureStruct;
                }
                break;

            case 'team':
                // Check for heading fields
                $headingFields = [];
                if (str_contains($content, 'A short and sweet title for this section.') ||
                    str_contains($content, 'Craft a captivating title for this section to attract your audience.')) {
                    $headingFields[] = ['name' => 'medium'];
                }
                if (!empty($headingFields)) {
                    $expectedStructure[] = [
                        'name' => 'heading',
                        'fields' => $headingFields
                    ];
                }

                if (str_contains($content, 'Use this space to write about your company')) {
                    $expectedStructure[] = [
                        'name' => 'sentence',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for overline
                if (str_contains($content, 'ADD AN OVERLINE') ||
                    str_contains($content, 'Add an overline') ||
                    str_contains($content, 'Overline')) {
                    $expectedStructure[] = [
                        'name' => 'overline',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for button
                if (str_contains($content, 'Call To Action') || str_contains($content, 'Call to Action')) {
                    $expectedStructure[] = [
                        'name' => 'button',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Team members
                if (str_contains($content, 'Name Lastname')) {
                    $peopleFields = [];
                    if (str_contains($content, 'Name Lastname')) {
                        $peopleFields[] = ['name' => 'name'];
                    }
                    if (str_contains($content, 'Position or title')) {
                        $peopleFields[] = ['name' => 'position'];
                    }
                    if (str_contains($content, 'Brief profile bio for this person will live here.')) {
                        $peopleFields[] = ['name' => 'sentence-short'];
                    }

                    $count = substr_count($content, 'Name Lastname');
                    $peopleStruct = [
                        'name' => 'people',
                        'fields' => $peopleFields
                    ];
                    if ($count > 1) {
                        $peopleStruct['repeat'] = $count;
                    }
                    $expectedStructure[] = $peopleStruct;
                }
                break;

            case 'counter-or-stats':
                // Check for heading fields
                $headingFields = [];
                if (str_contains($content, 'Tell your story in numbers')) {
                    $headingFields[] = ['name' => 'medium'];
                }
                if (!empty($headingFields)) {
                    $expectedStructure[] = [
                        'name' => 'heading',
                        'fields' => $headingFields
                    ];
                }

                if (str_contains($content, 'Make an impact, and share your organization\'s stats')) {
                    $expectedStructure[] = [
                        'name' => 'sentence',
                        'fields' => [['name' => 'medium']]
                    ];
                }

                // Check for overline
                if (str_contains($content, 'ADD AN OVERLINE TEXT') ||
                    str_contains($content, 'Add an overline text') ||
                    str_contains($content, 'Overline')) {
                    $expectedStructure[] = [
                        'name' => 'overline',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for button
                if (str_contains($content, 'Call To Action') || str_contains($content, 'Call to Action')) {
                    $expectedStructure[] = [
                        'name' => 'button',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Metrics
                if (str_contains($content, 'Stat title')) {
                    $metricsFields = [
                        ['name' => 'title-short'],
                        ['name' => 'value-short']
                    ];

                    $count = substr_count($content, 'Stat title');
                    $metricsStruct = [
                        'name' => 'metrics',
                        'fields' => $metricsFields
                    ];
                    if ($count > 1) {
                        $metricsStruct['repeat'] = $count;
                    }
                    $expectedStructure[] = $metricsStruct;
                }

                // List items
                if (str_contains($content, 'Add a single and succinct list item')) {
                    $listFields = [];
                    if (str_contains($content, 'Add a single and succinct list item')) {
                        $listFields[] = ['name' => 'list-item-short'];
                    }
                    if (str_contains($content, 'Add unique list items while keeping a consistent phrasing style')) {
                        $listFields[] = ['name' => 'list-item-long'];
                    }

                    $count = substr_count($content, 'Add a single and succinct list item');
                    $listStruct = [
                        'name' => 'list',
                        'fields' => $listFields
                    ];
                    if ($count > 1) {
                        $listStruct['repeat'] = $count;
                    }
                    $expectedStructure[] = $listStruct;
                }
                break;

            case 'table-of-contents':
                // Check for heading fields
                $headingFields = [];
                if (str_contains($content, 'Craft a captivating title for this section to attract your audience.')) {
                    $headingFields[] = ['name' => 'medium'];
                }
                if (!empty($headingFields)) {
                    $expectedStructure[] = [
                        'name' => 'heading',
                        'fields' => $headingFields
                    ];
                }

                // Check for overline
                if (str_contains($content, 'ADD AN OVERLINE') ||
                    str_contains($content, 'Add an overline') ||
                    str_contains($content, 'Overline')) {
                    $expectedStructure[] = [
                        'name' => 'overline',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for button
                if (str_contains($content, 'Call To Action') || str_contains($content, 'Call to Action')) {
                    $expectedStructure[] = [
                        'name' => 'button',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for subtitles
                if (str_contains($content, 'Write a title for your section or related content here')) {
                    $count = substr_count($content, 'Write a title for your section or related content here');
                    $subtitleStruct = [
                        'name' => 'subtitles',
                        'fields' => [['name' => 'title-short']]
                    ];
                    if ($count > 1) {
                        $subtitleStruct['repeat'] = $count;
                    }
                    $expectedStructure[] = $subtitleStruct;
                }
                break;

            case 'form':
                // Check for heading fields
                $headingFields = [];
                if (str_contains($content, 'Add A Title For Your Form') || str_contains($content, 'Contact Us')) {
                    $headingFields[] = ['name' => 'short'];
                }
                if (!empty($headingFields)) {
                    $expectedStructure[] = [
                        'name' => 'heading',
                        'fields' => $headingFields
                    ];
                }

                // Check for sentences
                $sentenceFields = [];
                if (str_contains($content, 'Briefly describe what the form is for')) {
                    $sentenceFields[] = ['name' => 'short'];
                }
                if (str_contains($content, 'Use this paragraph section to get your website visitors to know you.')) {
                    $sentenceFields[] = ['name' => 'long'];
                }
                if (!empty($sentenceFields)) {
                    $expectedStructure[] = [
                        'name' => 'sentence',
                        'fields' => $sentenceFields
                    ];
                }
                break;

            case 'text':
                // Check for headline fields
                $headingFields = [];
                if (str_contains($content, 'Type a short headline')) {
                    $headingFields[] = ['name' => 'short'];
                }
                if (str_contains($content, 'Briefly and concisely explain what you do for your audience.')) {
                    $headingFields[] = ['name' => 'medium'];
                }
                if (!empty($headingFields)) {
                    $expectedStructure[] = [
                        'name' => 'heading',
                        'fields' => $headingFields
                    ];
                }

                // Check for sentence fields
                $sentenceFields = [];
                if (str_contains($content, 'Use this paragraph section to get your website visitors to know you.')) {
                    $sentenceFields[] = ['name' => 'long'];
                }
                if (str_contains($content, 'Consider using this if you need to provide more context on why you do what you do. Be engaging. Focus on delivering value to your visitors.')) {
                    $sentenceFields[] = ['name' => 'medium'];
                }
                if (str_contains($content, 'Consider using this if you need to provide more context on why you do what you do.')) {
                    $sentenceFields[] = ['name' => 'short'];
                }
                if (!empty($sentenceFields)) {
                    $expectedStructure[] = [
                        'name' => 'sentence',
                        'fields' => $sentenceFields
                    ];
                }

                // Check for overline
                if (str_contains($content, '2018 - Current') ||
                    str_contains($content, 'Add an overline text') ||
                    str_contains($content, 'Overline')) {
                    $expectedStructure[] = [
                        'name' => 'overline',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for button
                if (str_contains($content, 'Call To Action') || str_contains($content, 'Call to Action')) {
                    $expectedStructure[] = [
                        'name' => 'button',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Column content
                if (str_contains($content, 'Add a short title')) {
                    $columnFields = [];
                    if (str_contains($content, 'Add a short title')) {
                        $columnFields[] = ['name' => 'title-short'];
                    }
                    if (str_contains($content, 'Use this space to add a short description.')) {
                        $columnFields[] = ['name' => 'sentence-short'];
                    }
                    if (str_contains($content, 'Use this space to add a medium length description.')) {
                        $columnFields[] = ['name' => 'sentence-medium'];
                    }

                    $count = substr_count($content, 'Add a short title');
                    $columnStruct = [
                        'name' => 'columns',
                        'fields' => $columnFields
                    ];
                    if ($count > 1) {
                        $columnStruct['repeat'] = $count;
                    }
                    $expectedStructure[] = $columnStruct;
                }
                break;

            case 'hero':
                // Check for headline fields
                $headingFields = [];
                if (str_contains($content, 'Write a brief title')) {
                    $headingFields[] = ['name' => 'short'];
                }
                if (str_contains($content, 'Briefly and concisely explain what you do for your audience.')) {
                    $headingFields[] = ['name' => 'medium'];
                }
                if (!empty($headingFields)) {
                    $expectedStructure[] = [
                        'name' => 'heading',
                        'fields' => $headingFields
                    ];
                }

                // Check for overline
                if (str_contains($content, 'ADD AN OVERLINE TEXT') ||
                    str_contains($content, 'Add an overline text') ||
                    str_contains($content, 'Overline')) {
                    $expectedStructure[] = [
                        'name' => 'overline',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for sentence fields
                if (str_contains($content, 'Consider using this if you need to provide more context')) {
                    $expectedStructure[] = [
                        'name' => 'sentence',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for buttons
                if (str_contains($content, 'Call To Action') || str_contains($content, 'Call to Action')) {
                    $expectedStructure[] = [
                        'name' => 'button',
                        'fields' => [['name' => 'short']]
                    ];
                }
                if (str_contains($content, 'Secondary Button')) {
                    $expectedStructure[] = [
                        'name' => 'secondary-button',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Cards content
                if (str_contains($content, 'Add a Title')) {
                    $cardFields = [];
                    if (str_contains($content, 'Add a Title')) {
                        $cardFields[] = ['name' => 'title-short'];
                    }
                    if (str_contains($content, 'Use this space to add a short description.')) {
                        $cardFields[] = ['name' => 'sentence-short'];
                    }
                    if (str_contains($content, 'Use this space to add a medium length description.')) {
                        $cardFields[] = ['name' => 'sentence-medium'];
                    }
                    if (str_contains($content, 'CTA')) {
                        $cardFields[] = ['name' => 'button-short'];
                    }

                    $count = substr_count($content, 'Add a Title');
                    $cardStruct = [
                        'name' => 'cards',
                        'fields' => $cardFields
                    ];
                    if ($count > 1) {
                        $cardStruct['repeat'] = $count;
                    }
                    $expectedStructure[] = $cardStruct;
                }
                break;

            case 'title-or-header':
                // Check for heading fields
                $headingFields = [];
                if (str_contains($content, 'Craft a captivating title for this section to attract your audience.') ||
                    str_contains($content, 'Craft a captivating title for the upcoming section to attract your audience.')) {
                    $headingFields[] = ['name' => 'medium'];
                }
                if (str_contains($content, 'Add a short & sweet headline') ||
                    str_contains($content, 'Add a short &amp; sweet headline')) {
                    $headingFields[] = ['name' => 'short'];
                }
                if (!empty($headingFields)) {
                    $expectedStructure[] = [
                        'name' => 'heading',
                        'fields' => $headingFields
                    ];
                }

                // Check for overline
                if (str_contains($content, 'ADD AN OVERLINE TEXT') ||
                    str_contains($content, 'Add an overline text') ||
                    str_contains($content, 'Overline')) {
                    $expectedStructure[] = [
                        'name' => 'overline',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for button
                if (str_contains($content, 'Call To Action') || str_contains($content, 'Call to Action')) {
                    $expectedStructure[] = [
                        'name' => 'button',
                        'fields' => [['name' => 'short']]
                    ];
                }
                break;

            case 'media-text':
                // Check for heading fields
                $headingFields = [];
                if (str_contains($content, 'Write a short headline')) {
                    $headingFields[] = ['name' => 'short'];
                }
                if (str_contains($content, 'Add a compelling title for your section to engage your audience.')) {
                    $headingFields[] = ['name' => 'medium'];
                }
                if (!empty($headingFields)) {
                    $expectedStructure[] = [
                        'name' => 'heading',
                        'fields' => $headingFields
                    ];
                }

                // Check for sentence
                if (str_contains($content, 'Use this paragraph section to get your website visitors to know you.')) {
                    $expectedStructure[] = [
                        'name' => 'sentence',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for overline
                if (str_contains($content, 'ADD AN OVERLINE') ||
                    str_contains($content, 'Add an overline') ||
                    str_contains($content, 'Overline')) {
                    $expectedStructure[] = [
                        'name' => 'overline',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for buttons
                if (str_contains($content, 'Call To Action') || str_contains($content, 'Call to Action')) {
                    $expectedStructure[] = [
                        'name' => 'button',
                        'fields' => [['name' => 'short']]
                    ];
                }
                if (str_contains($content, 'Secondary Button')) {
                    $expectedStructure[] = [
                        'name' => 'secondary-button',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for list items
                $listCount = substr_count($content, 'Add a list item');
                if ($listCount > 0) {
                    $listStruct = [
                        'name' => 'list',
                        'fields' => [['name' => 'list-item-short']]
                    ];
                    if ($listCount > 1) {
                        $listStruct['repeat'] = $listCount;
                    }
                    $expectedStructure[] = $listStruct;
                }

                // Check for columns
                if (str_contains($content, 'Add a descriptive title for the column.')) {
                    $columnFields = [];
                    if (str_contains($content, 'Add a descriptive title for the column.')) {
                        $columnFields[] = ['name' => 'title-medium'];
                    }
                    if (str_contains($content, 'Use this space to add a medium length description.')) {
                        $columnFields[] = ['name' => 'sentence-medium'];
                    }

                    $count = substr_count($content, 'Add a descriptive title for the column.');
                    $columnStruct = [
                        'name' => 'columns',
                        'fields' => $columnFields
                    ];
                    if ($count > 1) {
                        $columnStruct['repeat'] = $count;
                    }
                    $expectedStructure[] = $columnStruct;
                }
                break;

            case 'image':
                // Check for heading fields
                $headingFields = [];
                if (str_contains($content, 'Add a short, consistent heading for your image.')) {
                    $headingFields[] = ['name' => 'medium'];
                }
                if (str_contains($content, 'Add a short headline')) {
                    $headingFields[] = ['name' => 'short'];
                }
                if (!empty($headingFields)) {
                    $expectedStructure[] = [
                        'name' => 'heading',
                        'fields' => $headingFields
                    ];
                }

                // Check for sentence
                if (str_contains($content, 'Use this paragraph to add supporting context.')) {
                    $expectedStructure[] = [
                        'name' => 'sentence',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for button
                if (str_contains($content, 'Call To Action') || str_contains($content, 'Call to Action')) {
                    $expectedStructure[] = [
                        'name' => 'button',
                        'fields' => [['name' => 'short']]
                    ];
                }
                break;

            case 'accordion':
                // Check for heading fields
                $headingFields = [];
                if (str_contains($content, 'Add a short headline')) {
                    $headingFields[] = ['name' => 'short'];
                }
                if (str_contains($content, 'A brief headline here will add context for the section')) {
                    $headingFields[] = ['name' => 'medium'];
                }
                if (!empty($headingFields)) {
                    $expectedStructure[] = [
                        'name' => 'heading',
                        'fields' => $headingFields
                    ];
                }

                // Check for sentence
                if (str_contains($content, 'Use this space to provide your website visitors with a brief description')) {
                    $expectedStructure[] = [
                        'name' => 'sentence',
                        'fields' => [['name' => 'medium']]
                    ];
                }

                // Check for accordion items
                if (str_contains($content, 'Add a section title that is relevant for your readers.')) {
                    $accordionFields = [];
                    if (str_contains($content, 'Add a section title that is relevant for your readers.')) {
                        $accordionFields[] = ['name' => 'title-medium'];
                    }
                    if (str_contains($content, 'By default, this panel is concealed')) {
                        $accordionFields[] = ['name' => 'paragraph-medium'];
                    }

                    $count = substr_count($content, 'Add a section title that is relevant for your readers.');
                    $accordionStruct = [
                        'name' => 'accordion',
                        'fields' => $accordionFields
                    ];
                    if ($count > 1) {
                        $accordionStruct['repeat'] = $count;
                    }
                    $expectedStructure[] = $accordionStruct;
                }
                break;

            case 'tabs':
                // Check for heading fields
                $headingFields = [];
                if (str_contains($content, 'Add a short headline')) {
                    $headingFields[] = ['name' => 'short'];
                }
                if (!empty($headingFields)) {
                    $expectedStructure[] = [
                        'name' => 'heading',
                        'fields' => $headingFields
                    ];
                }

                // Check for sentence
                if (str_contains($content, 'Tabs are a helpful way that allow users')) {
                    $expectedStructure[] = [
                        'name' => 'sentence',
                        'fields' => [['name' => 'medium']]
                    ];
                }

                // Check for overline
                if (str_contains($content, 'ADD AN OVERLINE TEXT') ||
                    str_contains($content, 'Add an overline text') ||
                    str_contains($content, 'Overline')) {
                    $expectedStructure[] = [
                        'name' => 'overline',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Check for button
                if (str_contains($content, 'Call To Action') || str_contains($content, 'Call to Action')) {
                    $expectedStructure[] = [
                        'name' => 'button',
                        'fields' => [['name' => 'short']]
                    ];
                }

                // Tab content
                $countShort = substr_count($content, 'Tab name');
                $countMedium = substr_count($content, 'Give this tab a concise name');
                $count = max($countShort, $countMedium);

                if ($count > 0) {
                    $tabFields = [];
                    if (str_contains($content, 'Tab name')) {
                        $tabFields[] = ['name' => 'title-short'];
                    }
                    if (str_contains($content, 'Give this tab a concise name')) {
                        $tabFields[] = ['name' => 'title-medium'];
                    }
                    if (str_contains($content, 'Type a brief and clear title for this panel.')) {
                        $tabFields[] = ['name' => 'title-long'];
                    }
                    if (str_contains($content, 'Featured subhead')) {
                        $tabFields[] = ['name' => 'list-title'];
                    }
                    if (str_contains($content, 'Add a single and succinct list item')) {
                        $tabFields[] = ['name' => 'list-item-1'];
                        $tabFields[] = ['name' => 'list-item-2'];
                        $tabFields[] = ['name' => 'list-item-3'];
                    }
                    if (str_contains($content, 'Write a short descriptive paragraph about your tab')) {
                        $tabFields[] = ['name' => 'description-1'];
                    }
                    if (str_contains($content, 'This panel is hidden by default')) {
                        $tabFields[] = ['name' => 'description-2'];
                    }
                    if (str_contains($content, 'Tabs help users navigate through grouped content')) {
                        $tabFields[] = ['name' => 'description-3'];
                    }

                    $tabStruct = [
                        'name' => 'tabs',
                        'fields' => $tabFields
                    ];
                    if ($count > 1) {
                        $tabStruct['repeat'] = $count;
                    }
                    $expectedStructure[] = $tabStruct;
                }
                break;

            case 'testimonials':
                // Check for heading fields
                $headingFields = [];
                if (str_contains($content, 'Add a compelling title for your section to engage your audience.')) {
                    $headingFields[] = ['name' => 'medium'];
                }
                if (!empty($headingFields)) {
                    $expectedStructure[] = [
                        'name' => 'heading',
                        'fields' => $headingFields
                    ];
                }

                if (str_contains($content, 'Use this paragraph section to get your website visitors to know you.')) {
                    $expectedStructure[] = [
                        'name' => 'sentence',
                        'fields' => [['name' => 'long']]
                    ];
                }

                // Testimonial items
                if (str_contains($content, 'Customer Name')) {
                    $testimonialFields = [];
                    if (str_contains($content, 'Customer Name')) {
                        $testimonialFields[] = ['name' => 'customer'];
                    }
                    if (str_contains($content, 'Testimonials are a social proof') ||
                        str_contains($content, 'Testimonials, as authentic endorsements')) {
                        $testimonialFields[] = ['name' => 'testimonial'];
                    }

                    $count = substr_count($content, 'Customer Name');
                    $testimonialStruct = [
                        'name' => 'testimonials',
                        'fields' => $testimonialFields
                    ];
                    if ($count > 1) {
                        $testimonialStruct['repeat'] = $count;
                    }
                    $expectedStructure[] = $testimonialStruct;
                }
                break;
        }

        return $expectedStructure;
    }
}
