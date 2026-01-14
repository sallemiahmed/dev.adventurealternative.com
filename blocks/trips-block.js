wp.blocks.registerBlockType('custom/trips-block', {
    title: 'Trips Block',
    icon: 'admin-site-alt3',
    category: 'widgets',
    attributes: {
        selectedTrips: {
            type: 'array',
            default: [],
        },
        tripOptions: {
            type: 'array',
            default: [],
        },
    },
    edit: (props) => {
        const { attributes: { selectedTrips, tripOptions }, setAttributes } = props;

        // Fetch the list of trip posts if not already done
        if (tripOptions.length === 0) {
            wp.apiFetch({ path: '/custom/v1/trips' }).then((trips) => {
                const options = trips.map(trip => ({
                    value: trip.id.toString(),
                    label: trip.title,
                }));
                setAttributes({ tripOptions: options });
            }).catch((error) => {
                console.error('Error fetching trips:', error);
            });
        }

        const onChangeTrips = (selectedTripIDs) => {
            setAttributes({ selectedTrips: selectedTripIDs });
        };

        const loadingOptions = [{ value: '', label: 'Loading trips...' }];
        const options = tripOptions.length ? tripOptions : loadingOptions;

        return wp.element.createElement(
            'div',
            { className: 'trips-block' },
            wp.element.createElement('label', null, 'Select Up to Three Trips (Ctrl/Cmd+Click to Select Multiple):'),
            wp.element.createElement(wp.components.SelectControl, {
                multiple: true, // Allow multiple selections
                label: 'Trips',
                value: selectedTrips,
                options: options,
                onChange: onChangeTrips,
            })
        );
    },
    save: () => {
        return null; // We will render this on the server-side using PHP
    },
});
