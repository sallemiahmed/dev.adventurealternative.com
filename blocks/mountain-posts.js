wp.blocks.registerBlockType('custom/mountain-posts', {
    title: 'Mountain Posts',
    icon: 'mountains',
    category: 'widgets',
    attributes: {
        selectedMountain: {
            type: 'string',
        },
        mountainOptions: {
            type: 'array',
            default: [],
        },
    },
    edit: (props) => {
        const { attributes: { selectedMountain, mountainOptions }, setAttributes } = props;

        // Fetch the list of mountain posts if not already done
        if (mountainOptions.length === 0) {
            wp.apiFetch({ path: '/custom/v1/mountains' })
				.then((mountains) => {
					console.log(mountains); // Log the response
					const options = mountains.map(mountain => ({
						value: mountain.id.toString(),
						label: mountain.title,
					}));
					setAttributes({ mountainOptions: options });
				})
				.catch((error) => {
					console.error('Error fetching mountains:', error); // Log any error
				});

        }

        const onChangeMountain = (mountainID) => {
            setAttributes({ selectedMountain: mountainID });
        };

        // If no mountains are available yet, show a loading message
        const loadingOptions = [{ value: '', label: 'Loading mountains...' }];
        const options = mountainOptions.length ? mountainOptions : loadingOptions;

        return wp.element.createElement(
            'div',
            { className: 'mountain-posts-block' },
            wp.element.createElement('label', null, 'Select Mountain:'),
            wp.element.createElement(wp.components.SelectControl, {
                label: 'Mountain',
                value: selectedMountain,
                options: options,
                onChange: onChangeMountain,
            })
        );
    },
    save: () => {
        return null; // We will render this on the server-side using PHP
    },
});
