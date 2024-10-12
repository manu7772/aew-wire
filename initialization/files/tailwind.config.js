/** @type {import('tailwindcss').Config} */
module.exports = {
	content: [
		"./assets/**/*.{js,jsx}",
		"./templates/**/*.html.twig",
		"./vendor/aequation/wire/assets/**/*.{js,jsx}",
		"./vendor/aequation/wire/templates/**/*.html.twig",
	],
	darkMode: "class",
	theme: {
		extend: {
			colors: ({ colors }) => ({
				client_primary: 'rgb(4, 98, 20)',
				client_secondary: 'rgb(224, 0, 52)',
				client_overlay: 'rgba(98, 16, 35, 0.6)',
				// white: colors.gray[100],
				primary: colors.blue,
				secondary: colors.emerald,
				info: colors.cyan,
				success: colors.lime,
				warning: colors.orange,
				danger: colors.rose,
				error: colors.red,
				mdark: colors.amber,
				mwhite: colors.stone,
			}),
			spacing: {
				'8xl': '2560px',
			},
		},
		plugins: [],
	},
	plugins: [
		require('@tailwindcss/forms')({
		  strategy: 'base', // only generate global styles
		  // strategy: 'class', // only generate classes
		}),
	],
}
