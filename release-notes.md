
# What's New in ConfigWiz

### Version 2.1.0
**Released on:** 2025-03-28

**New Features:**
- Added support for Creo 12: ConfigWiz now fully supports Creo 12 with the necessary configurations for users to manage Creo 12 settings.
- Added Creo 12 config file: A Creo 12 config file has been added, though it requires additional refinement since it is still largely based on Creo 11.
  
**Improvements:**
- Enhanced default settings and added categories to improve the organization of configuration files.

---

### Version 2.0.1
**Released on:** 2025-03-26

**Fixes and Updates:**
- Improved default settings: Added better defaults for configurations, using PTC export from Creo 11, which provides users with predefined settings for more efficient setup.
- Added new categories to configuration files, helping to organize settings more effectively.

**Other Fixes:**
- Bug fixes in config file parsing and organization.

---

### Version 2.0.0
**Released on:** 2025-03-26

**Major Updates:**
- UI overhaul: The applicationâ€™s user interface has undergone significant improvements for better user experience, with clearer layouts and easier navigation.
- Category improvements: New categories have been introduced, helping users better organize configuration files.
  
**New Features:**
- Master config file: A master test_config.pro has been added to the configs folder, which includes every config from Creo 11, with de-defaulted values for testing purposes.

---

### Version 1.1.2
**Released on:** 2025-03-21

**Fixes:**
- Removed unused templates: The search_results.php template was removed as it was not being used in practice due to the instant search functionality.
  
**Improvements:**
- Improved clarity in version text: Reordered the text in the versions.php banner for better flow and understanding.

---

### Version 1.1.1
**Released on:** 2025-03-20

**New Features:**
- Config View Functionality: Added new functionality to view configurations directly from the summary template and JavaScript. Now when you view or download the config file, the parameters are formatted correctly, and new buttons have been added for viewing and downloading configurations.
  
**Enhancements:**
- Improved UI for config viewing: The config viewing page has been enhanced with copy to clipboard features, better dynamic content updates, and overall clearer design.

---

### Version 1.1.0
**Released on:** 2025-03-20

**New Features:**
- Improved config file generation: Config files can now include comments, disclaimers, and improved formatting to make them clearer and more informative.
- Updated the config metadata: Parameter metadata was updated to include additional descriptions, improving the understanding of certain parameters.
  
**Fixes and Updates:**
- Refined footer design: Cleaned up footer code by refactoring its implementation across multiple template files. A separate footer.php was included for easier maintenance.
- Updated footer text: The footer now accurately reflects the authorship and provides better contact accessibility.

---

### Version 1.0.1
**Released on:** 2025-03-16

**Bug Fixes:**
- Favicon updates: Removed old favicon references and replaced them with a new favicon.ico for consistent branding across the application.
- Footer styling fixes: The footer was updated to include a mailto link for direct contact with the author, Michael P. Bourque, making it easier to get in touch.

---

### Version 1.0.0
**Released on:** 2025-03-14

**New Features:**
- Initial release: The first public release of ConfigWiz included key features for managing configurations, including viewing configuration files and comparing them across versions of Creo.
- Google Analytics Integration: The first version also introduced Google Analytics for tracking user interactions.

**Fixes and Updates:**
- Initial bug fixes: Fixed minor issues in the UI and parameter descriptions to enhance user experience.

---

### Version 0.9.1
**Released on:** 2025-03-13

**Bug Fixes:**
- Fixed parameter display issues: Ensured that parameter metadata displays correctly, eliminating any inconsistencies in the parameter values.
- Styling fixes for mobile responsiveness: Minor fixes were applied to the CSS to ensure the app is mobile-friendly and performs well on smaller screens.

---

### Version 0.9.0
**Released on:** 2025-03-13

**New Features:**
- Initial configuration functionality: The first version of ConfigWiz allowed users to view, edit, and manage configuration files for Creo, making it easier to handle configuration parameters.

---

### Earlier Releases (Pre-0.9.0)

In the very early stages, ConfigWiz focused on building the core functionality of configuration management, laying the foundation for features like parameter comparison and config viewing. These releases were instrumental in establishing the apps structure and usability.

# Looking Ahead

ConfigWiz is evolving into a powerful tool for managing Creo configurations. The roadmap ahead focuses on transformative features designed to improve productivity, streamline workflows, and unlock smarter configuration strategies.

---

### **Config Wizard**
An interactive, step-by-step assistant that walks users through creating, modifying, and validating their configuration files. It simplifies the setup process, especially for new users or teams managing multiple environments.

---

### **Smart Recommendations**
Intelligent defaults and configuration suggestions based on best practices and user behavior. This feature helps streamline decisions by offering context-aware tips and parameters as you build your config.

---

### **Mapkey Manager**
A dedicated interface for viewing, organizing, and editing Creo mapkeys. Users will be able to manage key sequences visually, detect conflicts, and export mapkey sets more effectively.

---

### **Built-In Suggested Settings**
A curated library of recommended configurations for different use cases (e.g., drafting, modeling, performance). Users can apply these presets instantly, reducing setup time and improving consistency across teams.

---

### **Visual Color Pickers**
Inline graphical color selectors for parameters that accept color values. Users can now choose colors using visual tools instead of typing hex codes or RGB values.

---

### **Modelcheck and Config.win Support**
Expand ConfigWiz to handle additional configuration file types, including `modelcheck` and `config.win`. This allows users to manage a broader range of Creo customization settings from one interface.

---

### **Enhanced Import Experience**
More control during configuration import: preview changes, merge intelligently, and resolve conflicts before committing. This makes it easier to manage incoming settings without overwriting existing customizations.

---

### **Smarter Search and Navigation**
A redesigned search engine with category filters, fuzzy matching, and live previews. Users can locate parameters, metadata, and help faster, even in large configuration sets.

---

These planned enhancements reflect the next phase of ConfigWiz: smarter, faster, and more user-focused. Your feedback continues to shape the direction.

Thank you for helping build the future of configuration management.
