<x-three-level-hierarchical
        name="venue_category_id"
        label="Categories"
        :value="$result['venue_category_id'] ?? []"
        :data="$data['categories'] ?? []"
        :subcategorySource="$props['schema']['categories']['subcategory_source'] ?? []"
        :filterSource="$props['schema']['categories']['filter_source'] ?? []"
/>