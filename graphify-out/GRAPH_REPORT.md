# Graph Report - Zakat  (2026-08-20)

## Corpus Check
- 738 files · ~0 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 3264 nodes · 7431 edges · 182 communities (166 shown, 16 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 180 edges (avg confidence: 0.81)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Frontend Helpers
- Common Utilities
- Redux API Layer
- App Branding
- Redux API Layer 2
- Redux API Layer 3
- Frontend Routes
- Status Enums
- Layout Chrome
- Redux API Layer 4
- Ecommerce Pages
- Domain Models
- Shared Components
- Common Utilities 2
- Common Utilities 3
- Redux API Layer 5
- Shared Components 2
- Redux API Layer 6
- Redux API Layer 7
- App Layout
- App Layout 2
- Common Utilities 4
- Redux API Layer 8
- Status Enums 2
- Redux API Layer 9
- Shared Components 3
- Base UI Demos
- Redux API Layer 10
- Redux API Layer 11
- Charts UI
- Shared Components 4
- App Layout 3
- Redux API Layer 12
- Shared Components 5
- Ecommerce Pages 2
- Redux API Layer 13
- Redux API Layer 14
- Redux API Layer 15
- Ecommerce Pages 3
- Crypto Dashboard
- Common Utilities 5
- Redux API Layer 16
- Forms UI
- Redux API Layer 17
- Base UI Demos 2
- Common Utilities 6
- Redux API Layer 18
- Common Utilities 7
- Common Utilities 8
- Ecommerce Pages 4
- Base UI Demos 3
- Charts UI 2
- Charts UI 3
- Redux API Layer 19
- Common Utilities 9
- Redux API Layer 20
- Ecommerce Pages 5
- Redux API Layer 21
- Ecommerce Pages 6
- Base UI Demos 4
- Base UI Demos 5
- Crypto Dashboard 2
- Email UI
- Forms UI 2
- Frontend Routes 2
- Redux API Layer 22
- Charts UI 4
- Redux API Layer 23
- Redux API Layer 24
- Frontend Helpers 2
- Calendar
- Redux API Layer 25
- Forms UI 3
- App Layout 4
- Forms UI 4
- Projects Dashboard
- App Layout 5
- App Layout 6
- Redux API Layer 26
- Redux API Layer 27
- Base UI Demos 6
- Base UI Demos 7
- Charts UI 5
- Charts UI 6
- Ecommerce Dashboard
- Tickets UI
- Redux API Layer 28
- Base UI Demos 8
- Charts UI 7
- Charts UI 8
- Forms UI 5
- Icons Gallery
- Base UI Demos 9
- Tables UI
- Charts UI 9
- Crypto Dashboard 3
- Tasks UI
- Vite Env.D.Ts
- Foundation & Migrations
- Auth Controllers
- Shared Components 6
- Charts UI 10
- Charts UI 11
- Frontend Pages
- Shared Components 7
- Domain Models 2
- Charts UI 12
- Charts UI 13
- Redux API Layer 29
- Common Utilities 10
- Config.Ts
- Charts UI 14
- Charts UI 15
- Status Enums 3
- Auth Controllers 2
- Charts UI 16
- Redux API Layer 30
- Forms UI 6
- Redux API Layer 31
- Domain Models 3
- File Manager
- Icons Gallery 2
- Domain Models 4
- Common Utilities 11
- Middleware
- Domain Models 5
- Requests
- Status Enums 4
- Controllers
- Domain Models 6
- Package.Json
- Seeders
- Requests 2
- Auth Pages
- Auth Pages 2
- Shared Components 9
- Composer.Json
- Composer.Json 2
- Layout Chrome 2
- Controllers 2
- Frontend Routes 3
- Redux API Layer 32
- Base UI Demos 10
- Userinvitationnotification.Php
- Authservice.Php
- Composer.Json 3
- Composer.Json 4
- Forms UI 7
- Concerns
- Composer.Json 5
- Ecommerce Dashboard 2
- Charts UI 17
- Redux API Layer 33
- Composer.Json 6
- Composer.Json 7
- Logging.Php
- Ecommerce Dashboard 3
- Forms UI 8
- Status Enums 5
- Composer.Json 8
- Common Utilities 12
- Ecommerce Dashboard 4
- Ecommerce Dashboard 5
- Ecommerce Dashboard 6
- Forms UI 9
- Projects Dashboard 2
- Composer.Json 9
- Frontend Routes 4

## God Nodes (most connected - your core abstractions)
1. `getChartColorsArray()` - 195 edges
2. `BreadCrumb()` - 148 edges
3. `ApiResponse` - 60 edges
4. `User` - 59 edges
5. `Organization` - 49 edges
6. `ZakatException` - 40 edges
7. `UiModals()` - 36 edges
8. `OrganizationContext` - 36 edges
9. `PrismCode()` - 32 edges
10. `UiContent()` - 32 edges

## Surprising Connections (you probably didn't know these)
- `StackedColumn()` --calls--> `getChartColorsArray()`  [EXTRACTED]
  pages/Charts/ApexCharts/ColumnCharts/ColumnCharts.tsx → Components/Common/ChartsDynamicColor.tsx
- `StackedColumn2()` --calls--> `getChartColorsArray()`  [EXTRACTED]
  pages/Charts/ApexCharts/ColumnCharts/ColumnCharts.tsx → Components/Common/ChartsDynamicColor.tsx
- `LinewithDataLabels()` --calls--> `getChartColorsArray()`  [EXTRACTED]
  pages/Charts/ApexCharts/LineCharts/LineCharts.tsx → Components/Common/ChartsDynamicColor.tsx
- `Area()` --calls--> `getChartColorsArray()`  [EXTRACTED]
  pages/Charts/ApexCharts/MixedCharts/MixedCharts.tsx → Components/Common/ChartsDynamicColor.tsx
- `Line()` --calls--> `getChartColorsArray()`  [EXTRACTED]
  pages/Charts/ApexCharts/MixedCharts/MixedCharts.tsx → Components/Common/ChartsDynamicColor.tsx

## Import Cycles
- None detected.

## Communities (182 total, 16 thin omitted)

### Community 0 - "Frontend Helpers"
Cohesion: 0.01
Nodes (155): ADD_CANDIDATE_GRID, ADD_CATEGORY_LIST, ADD_MESSAGE, ADD_NEW_APPLICATION_LIST, ADD_NEW_CANDIDATE, ADD_NEW_COMPANIES, ADD_NEW_CONTACT, ADD_NEW_CUSTOMER (+147 more)

### Community 1 - "Common Utilities"
Cohesion: 0.07
Nodes (39): allaudiencesMetricsData, allData, currentyearaudiencesCountryData, currentYearDeviceData, halfyearaudiencesMetricsData, halfyearData, lastMonthaudiencesCountryData, lastMonthDeviceData (+31 more)

### Community 2 - "Redux API Layer"
Cohesion: 0.06
Nodes (50): languages, FullScreenDropdown(), LanguageDropdown(), LightDark(), LightDarkProps, MyCartDropdown(), NotificationDropdown(), ProfileDropdown() (+42 more)

### Community 3 - "App Branding"
Cohesion: 0.06
Nodes (59): APP_DESCRIPTION, APP_FULL_NAME, APP_LONG_DESCRIPTION, APP_NAME, APP_SHORT_TAGLINE, APP_TAGLINE, APP_TECHNICAL_TAGLINE, formatPageTitle() (+51 more)

### Community 4 - "Redux API Layer 2"
Cohesion: 0.05
Nodes (78): addMessage(), addNewTask(), addNewTasks(), addNewTicket(), api, deleteMessage(), deleteTask(), deleteTasks() (+70 more)

### Community 5 - "Redux API Layer 3"
Cohesion: 0.06
Nodes (22): NFTRanking, topCollection, walletConnectData, gallery, BreadCrumb(), ClassicEditor, UiSwiperSlider(), EcommerceAddProduct() (+14 more)

### Community 6 - "Frontend Routes"
Cohesion: 0.04
Nodes (39): UiScrollbar(), AuthSlider(), Alt404(), Basic404(), Cover404(), Error500(), Offlinepage(), BasicLockScreen() (+31 more)

### Community 7 - "Status Enums"
Cohesion: 0.06
Nodes (42): attachements, Spinners(), channelsListType, Chat(), chatContactDataTye, chatContactType, contact, DirectContact (+34 more)

### Community 9 - "Redux API Layer 4"
Cohesion: 0.12
Nodes (16): ActiveProjects(), Chat(), PrjectsStatusCharts(), ProjectsOverviewCharts(), TeamMembersCharts(), DashboardProject(), MyTasks(), ProjectsOverview() (+8 more)

### Community 10 - "Ecommerce Pages"
Cohesion: 0.06
Nodes (55): UiAnimation(), DurationExample(), EaseInBackExample(), EaseOutCubicExample(), EasingLinearExample(), FadeDownExample(), FadeDownLeftExample(), FadeDownRightExample() (+47 more)

### Community 11 - "Domain Models"
Cohesion: 0.08
Nodes (9): Organization, static, User, OrganizationService, OrganizationStatus, Illuminate\Database\Eloquent\Collection, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User (+1 more)

### Community 12 - "Shared Components"
Cohesion: 0.09
Nodes (20): featuredCompany, jobWidgets, recentApplicants, TableContainer(), TableContainerProps, SalesByLocations(), Candidates(), DashboardCharts() (+12 more)

### Community 13 - "Common Utilities 2"
Cohesion: 0.09
Nodes (27): jobApplication, jobCandidates, jobCategories, jobGrid, widgets, ecommerceWidget, otherWidgets2, tileBoxes4 (+19 more)

### Community 14 - "Common Utilities 3"
Cohesion: 0.16
Nodes (11): customerList, orders, orderSummary, productDetails, productDetailsWidgets, productsData, productsReview, revenueWidgets (+3 more)

### Community 15 - "Redux API Layer 5"
Cohesion: 0.11
Nodes (17): jobCompanies, jobList, Pagination(), CandidateGrid(), ImgData, CandidateList(), CompaniesList(), JobGrid() (+9 more)

### Community 16 - "Shared Components 2"
Cohesion: 0.14
Nodes (27): getChartColorsArray(), AreaChart(), BarLabelChart(), BasicBarChart(), BasicScatterChart(), CandleStickChart(), DoughnutChart(), FunnelChart() (+19 more)

### Community 17 - "Redux API Layer 6"
Cohesion: 0.10
Nodes (26): AddEditJobCandidateList(), ImgData, modal, handleSearchData(), getCandidateGrid(), getcategoryList(), getJobApplicationList(), getJobCandidateList() (+18 more)

### Community 18 - "Redux API Layer 7"
Cohesion: 0.10
Nodes (27): deleteCompanies(), deleteContact(), deleteEvent(), deleteFile(), deleteFolder(), deleteJobApplicationList(), deleteJobCandidate(), deleteLead() (+19 more)

### Community 19 - "App Layout"
Cohesion: 0.10
Nodes (15): Client(), Contact(), Counter(), Cta(), Faqs(), Features(), Footer(), Home() (+7 more)

### Community 20 - "App Layout 2"
Cohesion: 0.10
Nodes (17): connectData, discoverItemsData, featuresData, productData, topCreatorData, Connect(), CTA(), DiscoverItems() (+9 more)

### Community 21 - "Common Utilities 4"
Cohesion: 0.14
Nodes (16): allMarketplaceData, featuredNFTData, halfyearMarketplaceData, monthMarketplaceData, popularCreatorsData, popularityData, recentNFTsData, topartWork (+8 more)

### Community 22 - "Redux API Layer 8"
Cohesion: 0.15
Nodes (23): Loader(), getCompanies(), getContacts(), getDeals(), getLeads(), CrmCompanies(), CrmContacts(), CrmFilter() (+15 more)

### Community 23 - "Status Enums 2"
Cohesion: 0.05
Nodes (7): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, AmilTest, AuthorizationTest, CoreFoundationTest, OrganizationTest, TestCase

### Community 24 - "Redux API Layer 9"
Cohesion: 0.12
Nodes (19): AudiencesMetrics(), AudiencesSessions(), AudiencesCharts(), AudiencesSessionsCharts(), CountriesCharts(), UsersByDeviceCharts(), DashboardAnalytics(), LiveUsers() (+11 more)

### Community 25 - "Shared Components 3"
Cohesion: 0.12
Nodes (13): jobWidgets, Polarcharts(), BasicPolar(), PolarMonochrome(), RangeArea(), RangeAreaBasicChartCode(), RangeAreaChartCode(), PortfolioStatistics() (+5 more)

### Community 26 - "Base UI Demos"
Cohesion: 0.12
Nodes (17): CrossfadeAnimation(), DisableTouch(), IndividualInterval(), Slide(), SlideDark(), Slidewithcaption(), Slidewithcontrol(), Slidewithindicator() (+9 more)

### Community 27 - "Redux API Layer 10"
Cohesion: 0.11
Nodes (19): addCandidateGrid(), addcategoryList(), addJobCandidate(), addNewCompanies(), addNewContact(), addNewEvent(), addNewFile(), addNewFolder() (+11 more)

### Community 28 - "Redux API Layer 11"
Cohesion: 0.16
Nodes (18): ListView(), AssignedTo(), Client(), CreateDate(), DueDate(), handleValidDate(), Priority(), Status() (+10 more)

### Community 29 - "Charts UI"
Cohesion: 0.14
Nodes (21): AreaNullValueChart(), AxisChart(), BasicAreaCharts(), GithubStyleCharts(), GithubStyleCharts1(), IrregularAreaCharts(), NegativeAreaChart(), SplineAreaChart() (+13 more)

### Community 30 - "Shared Components 4"
Cohesion: 0.11
Nodes (17): allRevenueData, bestSellingProducts, ecomWidgets, halfYearRevenueData, monthRevenueData, recentOrders, topCategories, topSellers (+9 more)

### Community 31 - "App Layout 3"
Cohesion: 0.14
Nodes (13): categories, findJob, jobProcess, Blog(), Candidates(), Categories(), Features(), FindJob() (+5 more)

### Community 32 - "Redux API Layer 12"
Cohesion: 0.11
Nodes (17): MarkerCharts(), PortfolioCharts(), WidgetsCharts(), DashboardCrypto(), MarketGraph(), MyCurrencies(), MyPortfolio(), NewsFeed() (+9 more)

### Community 33 - "Shared Components 5"
Cohesion: 0.14
Nodes (12): CompaniesGlobalFilter(), ContactsGlobalFilter(), CryptoOrdersGlobalFilter(), CustomersGlobalFilter(), InvoiceListGlobalSearch(), LeadsGlobalFilter(), NFTRankingGlobalFilter(), OrderGlobalFilter() (+4 more)

### Community 34 - "Ecommerce Pages 2"
Cohesion: 0.17
Nodes (20): UiTypography(), BlockquoteBorderColorExample(), BlockquoteColorExample(), BlockquotesExample(), ClearfixExample(), DescriptionListExample(), DisplayHeadingExample(), FontSizeExample() (+12 more)

### Community 35 - "Redux API Layer 13"
Cohesion: 0.09
Nodes (32): ExportCSVModal(), ExportCSVModalProps, addNewCustomer(), addNewOrder(), addNewProduct(), deleteCustomer(), deleteOrder(), deleteProducts() (+24 more)

### Community 36 - "Redux API Layer 14"
Cohesion: 0.22
Nodes (12): getProjects(), getTodos(), ImgData, ToDoList(), initialState, TodosSlice, addNewProject, addNewTodo (+4 more)

### Community 37 - "Redux API Layer 15"
Cohesion: 0.31
Nodes (8): getTeamData(), Team(), initialState, TeamSlice, addTeamData, deleteTeamData, getTeamData, updateTeamData

### Community 38 - "Ecommerce Pages 3"
Cohesion: 0.18
Nodes (19): BasicTables(), ActiveTables(), BorderedTables(), Captions(), CardTables(), DefaultTables(), HoverableRows(), ResponsiveTables() (+11 more)

### Community 39 - "Crypto Dashboard"
Cohesion: 0.18
Nodes (10): MyWallet(), MarketStatus(), AvgPrice(), CurrentValue(), Quantity(), Returns(), RecentTransaction(), Watchlist() (+2 more)

### Community 40 - "Common Utilities 5"
Cohesion: 0.15
Nodes (11): activities, closingDeals, crmWidgets, dealsStatus, tasks, ClosingDeals(), DealsStatus(), DashboardCrm() (+3 more)

### Community 41 - "Redux API Layer 16"
Cohesion: 0.28
Nodes (14): DeleteModal(), DeleteModalProps, Calender(), MonthGridCalender(), UpcommingEvents(), calendarSlice, initialState, addNewEvent (+6 more)

### Community 42 - "Forms UI"
Cohesion: 0.24
Nodes (9): animatedComponents, GroupOptions, GroupOptions2, noSortingGroup, options, SingleOptions, DefaultSelect(), MenuSize() (+1 more)

### Community 43 - "Redux API Layer 17"
Cohesion: 0.21
Nodes (13): TableContainer(), Revenue(), handleValidDate(), handleValidTime(), Price(), Published(), Rating(), EcommerceProducts() (+5 more)

### Community 44 - "Base UI Demos 2"
Cohesion: 0.19
Nodes (18): ActiveItemExample(), ColoredListExample(), ContextualClassExample(), ContextualLinkExample(), CustomContentExample(), CustomListExample(), DefaultListExample(), DisabledItemExample() (+10 more)

### Community 45 - "Common Utilities 6"
Cohesion: 0.18
Nodes (11): blogwidget, comments, recentTable, socialShares, DashboardBlogCharts(), DeviceCharts(), Device(), DashboardBlog() (+3 more)

### Community 47 - "Redux API Layer 18"
Cohesion: 0.15
Nodes (20): APIClient, getFiles(), getFolders(), updateEvent(), updateFile(), updateFolder(), updateJobApplicationList(), updateTeamData() (+12 more)

### Community 48 - "Common Utilities 7"
Cohesion: 0.17
Nodes (12): aution, creatorsData, creatorsListData, marketPlacewidget, nftArtworkData, popularCreatorsNFT, topCreator, topDrop (+4 more)

### Community 49 - "Common Utilities 8"
Cohesion: 0.16
Nodes (13): documents, news, pricing1, pricing2, pricing3, projects, SearchGallery, swiper (+5 more)

### Community 50 - "Ecommerce Pages 4"
Cohesion: 0.22
Nodes (15): UiAccordions(), BorderedAccordionExample(), CollapseExample(), DefaultAccordionExample(), FillColoredAccordionExample(), FlushAccordionExample(), HorizontalCollapseExample(), IconAccordionExample() (+7 more)

### Community 51 - "Base UI Demos 3"
Cohesion: 0.22
Nodes (15): UiProgress(), AnimatedExample(), AnimatedStripedExample(), BackgroundColorExample(), ContentExample(), CustomExample(), CustomProgressExample(), DefaultProgressExample() (+7 more)

### Community 52 - "Charts UI 2"
Cohesion: 0.21
Nodes (15): BasicColumn(), ColumnGroupLabels(), ColumnMarker(), ColumnWithLable(), DistributedColumn(), DumbbellChartColors(), DynamicColumn(), GroupStacked() (+7 more)

### Community 53 - "Charts UI 3"
Cohesion: 0.23
Nodes (15): LineCharts(), BasicLineCharts(), BrushChart(), BrushChart1(), ChartSyncingArea(), ChartSyncingLine(), ChartSyncingLine2(), DashedLine() (+7 more)

### Community 54 - "Redux API Layer 19"
Cohesion: 0.11
Nodes (12): root, store, reportWebVitals(), forgotPasswordSlice, initialState, initialState, loginSlice, DashboardEcommerceSlice (+4 more)

### Community 55 - "Common Utilities 9"
Cohesion: 0.16
Nodes (11): buysellWidgets, CryptoicoWidgets, CryptoOrders, icoWidgetsList, market, marketStatus, transactions, watchlist (+3 more)

### Community 56 - "Redux API Layer 20"
Cohesion: 0.19
Nodes (12): addProjectList(), deleteProjectList(), getProjectList(), updateProjectList(), ProjectList(), List(), initialState, ProjectsSlice (+4 more)

### Community 57 - "Ecommerce Pages 5"
Cohesion: 0.23
Nodes (14): UiAlerts(), AdditionalContentAlertsExample(), BorderlessExample(), DefaultAlertsExample(), DismissingExample(), LabelIconAlertsExample(), LabelIconArrowAlertsExample(), LeftBorderAlertsExample() (+6 more)

### Community 58 - "Redux API Layer 21"
Cohesion: 0.21
Nodes (11): APIKeys(), CreatedBy(), CreatedDate(), ExpiryDate(), Name(), Status(), APIKey(), Widgets() (+3 more)

### Community 59 - "Ecommerce Pages 6"
Cohesion: 0.25
Nodes (13): UiBadges(), ButtonBadgesExample(), ButtonPositionBadgesExample(), DefaultBadgesExample(), GradientBadgesExample(), HTMLBadgesExample(), LabelBadgesExample(), OutlineBadgesExample() (+5 more)

### Community 60 - "Base UI Demos 4"
Cohesion: 0.26
Nodes (12): UiDropdowns(), AlignDropdownExample(), AutoCloseDropdownExample(), ColorDropdownExample(), DarkDropdownExample(), MenuContentDropdownExample(), MenuItemDropdownExample(), NotificationDropdownExample() (+4 more)

### Community 61 - "Base UI Demos 5"
Cohesion: 0.43
Nodes (6): UiGrid(), AlignSelfExample(), HorizontalAlignExample(), VerticalCenterExample(), VerticalEndExample(), VerticalStartExample()

### Community 62 - "Crypto Dashboard 2"
Cohesion: 0.23
Nodes (9): BuySellCoin(), BuySell(), Market(), HighPrice(), LowPrice(), MarketVolume(), Pairs(), Price() (+1 more)

### Community 63 - "Email UI"
Cohesion: 0.19
Nodes (5): BasicAction(), EmailVerifyAction(), index(), PasswordChangeAction(), SubscribeAction()

### Community 64 - "Forms UI 2"
Cohesion: 0.26
Nodes (12): CheckBoxAndRadio(), Checkbox(), CustomCheckbox(), CustomRadio(), CustomSwitches(), InlineCheckboxRadio(), OutlinedStyles(), Radio() (+4 more)

### Community 65 - "Frontend Routes 2"
Cohesion: 0.33
Nodes (5): App(), fakeBackend(), authProtectedRoutes, publicRoutes, Index()

### Community 66 - "Redux API Layer 22"
Cohesion: 0.35
Nodes (7): useProfile(), authUser, getLoggedinUser(), setAuthorization(), Logout(), AuthProtected(), logoutUser()

### Community 67 - "Charts UI 4"
Cohesion: 0.28
Nodes (11): BarwithImages(), Basic(), CustomDataLabel(), Groupes(), Markers(), Negative(), Patterned(), Reversed() (+3 more)

### Community 68 - "Redux API Layer 23"
Cohesion: 0.24
Nodes (10): AllTransactions(), Transactions(), DetailsCol(), FromCol(), Status(), ToCol(), TransactionID(), TypeCol() (+2 more)

### Community 69 - "Redux API Layer 24"
Cohesion: 0.19
Nodes (11): BalanceOverview(), BalanceOverviewCharts(), DealTypeCharts(), SalesForecastCharts(), DealType(), SalesForecast(), DashboardCRMSlice, initialState (+3 more)

### Community 71 - "Calendar"
Cohesion: 0.30
Nodes (10): UiUtilities(), HeightExample(), OverflowExample(), PointerEventsExample(), PositionExample(), ShadowsExample(), StacksHorizontalExample(), StacksVerticalExample() (+2 more)

### Community 72 - "Redux API Layer 25"
Cohesion: 0.21
Nodes (11): AllOrders(), CryproOrder(), AvgPrice(), OrderValue(), Price(), Quantity(), Status(), Type() (+3 more)

### Community 73 - "Forms UI 3"
Cohesion: 0.30
Nodes (10): ButtonsCheckboxesRadiosGroup(), ButtonsWithDropdowns(), CustomForms(), FileInput(), InputExample(), InputGroup(), InputGroupSizing(), InputSizing() (+2 more)

### Community 74 - "App Layout 4"
Cohesion: 0.30
Nodes (10): Formlayouts(), AutoSizing(), ColumnSizing(), FloatingLabels(), FormGrid(), Gutters(), HorizontalForm(), HorizontalFormLabelSizing() (+2 more)

### Community 76 - "Projects Dashboard"
Cohesion: 0.24
Nodes (6): ActivitiesTab(), DocumentsTab(), ProjectOverview(), OverviewTab(), Section(), TeamTab()

### Community 77 - "App Layout 5"
Cohesion: 0.25
Nodes (6): overviewJobs, Header(), JobOverview(), JobDescription(), RelatedJobs(), RightSection()

### Community 78 - "App Layout 6"
Cohesion: 0.20
Nodes (11): PreviewCardHeader(), UiNotifications(), BootstrapToastsExample(), BorderIconExample(), ToastifyExample(), ToastPlacementExample(), FormValidations(), BrowserDefaults() (+3 more)

### Community 79 - "Redux API Layer 26"
Cohesion: 0.27
Nodes (8): postFakeRegister(), postJwtRegister(), Register(), initialState, registerSlice, fireBaseBackend, registerUser(), resetRegisterFlag()

### Community 80 - "Redux API Layer 27"
Cohesion: 0.33
Nodes (8): postFakeLogin(), postJwtLogin(), getFirebaseBackend(), Login(), loginUser(), persistAuthUser(), resetLoginFlag(), socialLogin()

### Community 81 - "Base UI Demos 6"
Cohesion: 0.36
Nodes (8): UiImages(), AvatarExample(), AvatarGroupExample(), FiguresExample(), ImgRoundedCircleExample(), ImgSizesExample(), ImgThumbnailsExample(), ResponsiveExample()

### Community 82 - "Base UI Demos 7"
Cohesion: 0.36
Nodes (8): UiLink(), ColorExample(), DefaultLinkExample(), OffsetExample(), OpacityExample(), OpacityHoverExample(), UtilitiesExample(), UtilityOpacityExample()

### Community 83 - "Charts UI 5"
Cohesion: 0.36
Nodes (8): PieCharts(), GradientDonut(), ImagePieChart(), MonochromePie(), PatternedDonut(), SimpleDonut(), SimplePie(), UpdateDonut()

### Community 84 - "Charts UI 6"
Cohesion: 0.36
Nodes (8): RadialbarCharts(), CircleRadialbar(), GradientCircleRadialbar(), ImageRadialbar(), MultipleRadialbar(), SemiCircularRadial(), SimpleRadialbar(), StrokedCircleRadial()

### Community 85 - "Ecommerce Dashboard"
Cohesion: 0.27
Nodes (4): FeedbackAction(), index(), InvoiceAction(), RatingTemplate()

### Community 86 - "Tickets UI"
Cohesion: 0.29
Nodes (5): TicketsDetaiks(), Section(), TicketDescription(), TicketDetails(), TicketCodeExample()

### Community 87 - "Redux API Layer 28"
Cohesion: 0.23
Nodes (10): addNewInvoice(), deleteInvoice(), getInvoices(), updateInvoice(), InvoiceList(), initialState, InvoiceSlice, deleteInvoice (+2 more)

### Community 88 - "Base UI Demos 8"
Cohesion: 0.39
Nodes (7): UiRibbons(), BoxedRibbonsExample(), FilledRibbonsExample(), RibbonsExample(), RibbonShapeExample(), RibbonsHoverExample(), RoundedRibbonExample()

### Community 89 - "Charts UI 7"
Cohesion: 0.39
Nodes (7): TimelineCharts(), Advanced(), Basic(), DifferentColor(), DumbBell(), MultipleSeries(), MultiSeries()

### Community 90 - "Charts UI 8"
Cohesion: 0.39
Nodes (7): BarChart(), DonutChart(), LineChart(), PieChart(), PolarChart(), RadarChart(), ChartsJs()

### Community 91 - "Forms UI 5"
Cohesion: 0.25
Nodes (5): FormAdvanced(), MultiSelect(), Optgroup, OptgroupFilter, options

### Community 92 - "Icons Gallery"
Cohesion: 0.39
Nodes (7): Materialdesign(), renderIconGrid(), materialAllIcons, materialNewIcons, materialRotateIcons, materialSizeIcons, materialSpinIcons

### Community 93 - "Base UI Demos 9"
Cohesion: 0.43
Nodes (6): UiEmbedVideo(), CustomRationExample(), Ratio11Example(), Ratio169Example(), Ratio219Example(), Ratio43Example()

### Community 94 - "Tables UI"
Cohesion: 0.43
Nodes (6): UiGeneral(), BreadcrumbExample(), PaginationExample(), PopoversExample(), SpinnersExample(), TooltipsExample()

### Community 95 - "Charts UI 9"
Cohesion: 0.54
Nodes (6): BasicHeatmap(), ColorRange(), generateData(), MultipleHeatmap(), RangeWithoutShades(), HeatmapCharts()

### Community 96 - "Crypto Dashboard 3"
Cohesion: 0.32
Nodes (4): KYCVerification(), KYCVerification(), formatBytes(), handleAcceptedFiles()

### Community 97 - "Tasks UI"
Cohesion: 0.36
Nodes (4): Comments(), TaskDetails(), Summary(), TimeTracking()

### Community 98 - "Vite Env.D.Ts"
Cohesion: 0.25
Nodes (7): *.gif, *.jpeg, *.jpg, *.png, *.scss, *.svg, *.webp

### Community 99 - "Foundation & Migrations"
Cohesion: 0.07
Nodes (7): AppServiceProvider, Illuminate\Auth\Notifications\ResetPassword, Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\Date, Illuminate\Support\Facades\Schema, Illuminate\Support\ServiceProvider

### Community 100 - "Auth Controllers"
Cohesion: 0.10
Nodes (13): AmilAssignmentResource, AmilResource, OrganizationAddressResource, OrganizationContactResource, OrganizationResource, OrganizationSummaryResource, RoleResource, RoleSummaryResource (+5 more)

### Community 101 - "Shared Components 6"
Cohesion: 0.15
Nodes (14): BreadCrumbProps, UiContent(), UiCards(), UiColors(), UiOffcanvas(), BackdropOffcanvasExample(), DefaultOffcanvasExample(), PlacementOffcanvasExample() (+6 more)

### Community 102 - "Charts UI 10"
Cohesion: 0.48
Nodes (5): MixedCharts(), Area(), Line(), LineColumnArea(), YAxis()

### Community 103 - "Charts UI 11"
Cohesion: 0.48
Nodes (5): TreemapCharts(), BasicTreemap(), ColorRangeTreemap(), DiffColorTreemap(), MultiTreemap()

### Community 104 - "Frontend Pages"
Cohesion: 0.29
Nodes (6): center, containerStyle, fourth, GoogleMaps(), second, third

### Community 106 - "Domain Models 2"
Cohesion: 0.11
Nodes (15): AuditLog, CodeRegistry, organization(), scopeAcrossOrganizations(), OrganizationAddress, OrganizationContact, Permission, OrganizationScope (+7 more)

### Community 107 - "Charts UI 12"
Cohesion: 0.53
Nodes (4): Basic(), Horizontal(), Scatter(), BoxplotCharts()

### Community 108 - "Charts UI 13"
Cohesion: 0.53
Nodes (4): RadarCharts(), MultipleRadar(), PolygonRadar(), SimpleRadar()

### Community 109 - "Redux API Layer 29"
Cohesion: 0.47
Nodes (3): EcommerceSellers(), SellerChats(), getSellers

### Community 110 - "Common Utilities 10"
Cohesion: 0.40
Nodes (4): companies, crmcontacts, deals, leads

### Community 111 - "Config.Ts"
Cohesion: 0.40
Nodes (4): ApiConfig, Config, FacebookConfig, GoogleConfig

### Community 112 - "Charts UI 14"
Cohesion: 0.60
Nodes (3): Simple(), ThreeDBubble(), BubbleChart()

### Community 113 - "Charts UI 15"
Cohesion: 0.60
Nodes (3): FunnelChartCode(), PyramidChartCode(), FunnelCharts()

### Community 114 - "Status Enums 3"
Cohesion: 0.10
Nodes (8): AmilController, AmilStatus, UserStatus, UserController, StoreAmilAssignmentRequest, StoreAmilRequest, UpdateAmilRequest, Illuminate\Http\JsonResponse

### Community 115 - "Auth Controllers 2"
Cohesion: 0.06
Nodes (11): AuthController, AcceptInvitationRequest, ApiRequest, ChangePasswordRequest, ForgotPasswordRequest, LoginRequest, ResetPasswordRequest, SwitchOrganizationRequest (+3 more)

### Community 116 - "Charts UI 16"
Cohesion: 0.60
Nodes (3): SlopeCharts(), BasicSlop(), MultiGroup()

### Community 117 - "Redux API Layer 30"
Cohesion: 0.47
Nodes (3): CrmDeals(), leadDiscover(), getDeals

### Community 118 - "Forms UI 6"
Cohesion: 0.40
Nodes (3): Optgroup, OptgroupFilter, options

### Community 121 - "Domain Models 3"
Cohesion: 0.08
Nodes (15): save(), BusinessNumberService, OrganizationFactory, static, static, UserFactory, Illuminate\Database\Eloquent\Attributes\Hidden, Illuminate\Database\Eloquent\Factories\Factory (+7 more)

### Community 122 - "File Manager"
Cohesion: 0.67
Nodes (3): FileUpload(), formatBytes(), handleAcceptedFiles()

### Community 124 - "Domain Models 4"
Cohesion: 0.12
Nodes (7): ErrorCode, self, ZakatException, bootBelongsToOrganization(), Role, RoleService, OrganizationContext

### Community 125 - "Common Utilities 11"
Cohesion: 0.06
Nodes (48): apiKey, country, btcPortfolioData, cryptoSlider, currencies, cyptoWidgets, euroPortfolioData, MarketGraphAll (+40 more)

### Community 126 - "Middleware"
Cohesion: 0.08
Nodes (18): AssignRequestId, EnsurePermission, ResolveOrganizationContext, RequestId, Closure, Illuminate\Auth\Access\AuthorizationException, Illuminate\Auth\AuthenticationException, Illuminate\Database\Eloquent\ModelNotFoundException (+10 more)

### Community 129 - "Domain Models 5"
Cohesion: 0.11
Nodes (5): OrganizationMember, AuditService, MembershipService, MembershipStatus, Illuminate\Contracts\Pagination\LengthAwarePaginator

### Community 130 - "Requests"
Cohesion: 0.11
Nodes (6): RoleController, StoreRoleRequest, SyncPermissionsRequest, UpdateRoleRequest, ApiResponse, Illuminate\Http\Resources\Json\ResourceCollection

### Community 131 - "Status Enums 4"
Cohesion: 0.13
Nodes (4): UserInvitation, UserStatus, UserService, Illuminate\Support\Facades\Notification

### Community 132 - "Controllers"
Cohesion: 0.14
Nodes (6): OrganizationMemberController, MembershipStatus, ListRequest, StoreMemberRequest, UpdateMemberRequest, OrganizationMemberResource

### Community 133 - "Domain Models 6"
Cohesion: 0.15
Nodes (4): Amil, AmilAssignment, AmilService, AmilStatus

### Community 134 - "Package.Json"
Cohesion: 0.10
Nodes (20): concurrently, @laravel/multiplex, laravel-vite-plugin, devDependencies, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite (+12 more)

### Community 135 - "Seeders"
Cohesion: 0.15
Nodes (7): BootstrapSeeder, CodeRegistrySeeder, DatabaseSeeder, PermissionSeeder, RoleSeeder, Illuminate\Database\Seeder, Illuminate\Support\Facades\RateLimiter

### Community 136 - "Requests 2"
Cohesion: 0.11
Nodes (5): StoreOrganizationRequest, StoreUserRequest, SyncRolesRequest, UpdateUserRequest, Illuminate\Validation\Rule

### Community 137 - "Auth Pages"
Cohesion: 0.18
Nodes (3): SessionController, SessionResource, AuthService

### Community 139 - "Shared Components 9"
Cohesion: 0.21
Nodes (10): PrismCode(), PrismCodeProps, UiHighlight(), CssHighlightExample(), HtmlHighlightExample(), JavaScriptExample(), UiMediaobject(), DefultExample() (+2 more)

### Community 140 - "Composer.Json"
Cohesion: 0.14
Nodes (13): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+5 more)

### Community 141 - "Composer.Json 2"
Cohesion: 0.14
Nodes (14): scripts, dev, post-autoload-dump, post-update-cmd, pre-package-uninstall, test, Composer\\Config::disableProcessTimeout, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump (+6 more)

### Community 142 - "Layout Chrome 2"
Cohesion: 0.27
Nodes (12): AnimationModalExample(), CenteredModalExample(), DefaultModalExample(), FullscreenResponsiveExample(), GridsModalExample(), OptionalModalExample(), PositionModalExample(), ScrollableModalExample() (+4 more)

### Community 143 - "Controllers 2"
Cohesion: 0.24
Nodes (3): OrganizationController, OrganizationStatus, UpdateOrganizationRequest

### Community 144 - "Frontend Routes 3"
Cohesion: 0.22
Nodes (4): PermissionController, Controller, PermissionResource, Illuminate\Support\Facades\Route

### Community 145 - "Redux API Layer 32"
Cohesion: 0.27
Nodes (8): postFakeProfile(), postJwtProfile(), UserProfile(), initialState, ProfileSlice, editProfile(), fireBaseBackend, resetProfileFlag()

### Community 146 - "Base UI Demos 10"
Cohesion: 0.39
Nodes (7): UiRatings(), BasicRaterExample(), CustomMsgExample(), OnHoverExample(), RaterWithStepExample(), ReadOnlyRaterExample(), ReasetRaterExample()

### Community 147 - "Userinvitationnotification.Php"
Cohesion: 0.36
Nodes (4): UserInvitationNotification, Illuminate\Bus\Queueable, Illuminate\Notifications\Messages\MailMessage, Illuminate\Notifications\Notification

### Community 148 - "Authservice.Php"
Cohesion: 0.25
Nodes (5): Illuminate\Support\Collection, Illuminate\Support\Facades\Auth, Illuminate\Support\Facades\Hash, Illuminate\Support\Facades\Password, Illuminate\Support\Facades\Request

### Community 149 - "Composer.Json 3"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pao, laravel/pint, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 150 - "Composer.Json 4"
Cohesion: 0.25
Nodes (8): post-root-package-install, setup, composer install, npm install --ignore-scripts, npm run build, @php artisan key:generate, @php artisan migrate --force, @php -r \"file_exists('.env') || copy('.env.example', '.env');\

### Community 152 - "Concerns"
Cohesion: 0.48
Nodes (6): auditAttributes(), auditOrganizationId(), auditPrefix(), bootAuditable(), recordAudit(), Illuminate\Support\Arr

### Community 153 - "Composer.Json 5"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 155 - "Charts UI 17"
Cohesion: 0.53
Nodes (4): ScatterCharts(), Basic(), Datetime(), ImagesChart()

### Community 156 - "Redux API Layer 33"
Cohesion: 0.47
Nodes (4): MarketplaceChart(), TopArtworkChart(), Marketplace(), getMarketChartsDatas

### Community 157 - "Composer.Json 6"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 158 - "Composer.Json 7"
Cohesion: 0.40
Nodes (5): require, laravel/framework, laravel/sanctum, laravel/tinker, php

### Community 159 - "Logging.Php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 160 - "Ecommerce Dashboard 3"
Cohesion: 0.50
Nodes (3): RevenueCharts(), StoreVisitsCharts(), StoreVisits()

### Community 162 - "Status Enums 5"
Cohesion: 0.67
Nodes (3): allowedNext(), canTransitionTo(), self

### Community 163 - "Composer.Json 8"
Cohesion: 0.50
Nodes (4): post-create-project-cmd, @php artisan key:generate --ansi, @php artisan migrate --graceful --ansi, @php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\

### Community 169 - "Projects Dashboard 2"
Cohesion: 0.67
Nodes (3): CreateProject(), formatBytes(), handleAcceptedFiles()

### Community 170 - "Composer.Json 9"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

## Knowledge Gaps
- **350 isolated node(s):** `BreadCrumbProps`, `DefaultColumnProps`, `SelectColumnFilterProps`, `ApiConfig`, `Config` (+345 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **16 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `BreadCrumb()` connect `Redux API Layer 3` to `App Branding`, `Frontend Routes`, `Status Enums`, `Redux API Layer 4`, `Ecommerce Pages`, `Shared Components`, `Common Utilities 2`, `Common Utilities 3`, `Redux API Layer 5`, `Shared Components 2`, `Redux API Layer 6`, `Common Utilities 4`, `Redux API Layer 8`, `Redux API Layer 9`, `Shared Components 3`, `Base UI Demos`, `Redux API Layer 11`, `Charts UI`, `Redux API Layer 12`, `Ecommerce Pages 2`, `Redux API Layer 13`, `Redux API Layer 15`, `Ecommerce Pages 3`, `Crypto Dashboard`, `Common Utilities 5`, `Redux API Layer 16`, `Forms UI`, `Redux API Layer 17`, `Base UI Demos 2`, `Common Utilities 6`, `Common Utilities 7`, `Common Utilities 8`, `Ecommerce Pages 4`, `Base UI Demos 3`, `Charts UI 2`, `Charts UI 3`, `Common Utilities 9`, `Redux API Layer 20`, `Ecommerce Pages 5`, `Redux API Layer 21`, `Ecommerce Pages 6`, `Base UI Demos 4`, `Base UI Demos 5`, `Crypto Dashboard 2`, `Email UI`, `Forms UI 2`, `Charts UI 4`, `Redux API Layer 23`, `Calendar`, `Redux API Layer 25`, `Forms UI 3`, `App Layout 4`, `Forms UI 4`, `App Layout 6`, `Base UI Demos 6`, `Base UI Demos 7`, `Charts UI 5`, `Charts UI 6`, `Ecommerce Dashboard`, `Redux API Layer 28`, `Base UI Demos 8`, `Charts UI 7`, `Charts UI 8`, `Forms UI 5`, `Icons Gallery`, `Base UI Demos 9`, `Tables UI`, `Charts UI 9`, `Crypto Dashboard 3`, `Tasks UI`, `Shared Components 6`, `Charts UI 10`, `Charts UI 11`, `Frontend Pages`, `Charts UI 12`, `Charts UI 13`, `Redux API Layer 29`, `Charts UI 14`, `Charts UI 15`, `Charts UI 16`, `Redux API Layer 30`, `Redux API Layer 31`, `File Manager`, `Icons Gallery 2`, `Shared Components 9`, `Layout Chrome 2`, `Base UI Demos 10`, `Ecommerce Dashboard 2`, `Charts UI 17`, `Forms UI 8`, `Common Utilities 12`, `Ecommerce Dashboard 6`, `Forms UI 9`?**
  _High betweenness centrality (0.076) - this node is a cross-community bridge._
- **Why does `getChartColorsArray()` connect `Shared Components 2` to `Redux API Layer 4`, `Common Utilities 2`, `Redux API Layer 5`, `Common Utilities 4`, `Redux API Layer 9`, `Shared Components 3`, `Charts UI 17`, `Redux API Layer 33`, `Charts UI`, `Redux API Layer 12`, `Ecommerce Dashboard 3`, `Common Utilities 6`, `Charts UI 2`, `Charts UI 3`, `Charts UI 4`, `Redux API Layer 24`, `Charts UI 5`, `Charts UI 6`, `Charts UI 7`, `Charts UI 8`, `Charts UI 9`, `Charts UI 10`, `Charts UI 11`, `Charts UI 12`, `Charts UI 13`, `Charts UI 14`, `Charts UI 15`, `Charts UI 16`?**
  _High betweenness centrality (0.021) - this node is a cross-community bridge._
- **Why does `FormSelect()` connect `Forms UI 7` to `Forms UI`, `Frontend Routes`?**
  _High betweenness centrality (0.009) - this node is a cross-community bridge._
- **What connects `BreadCrumbProps`, `DefaultColumnProps`, `SelectColumnFilterProps` to the rest of the system?**
  _350 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Frontend Helpers` be split into smaller, more focused modules?**
  _Cohesion score 0.01282051282051282 - nodes in this community are weakly interconnected._
- **Should `Common Utilities` be split into smaller, more focused modules?**
  _Cohesion score 0.07215541165587419 - nodes in this community are weakly interconnected._
- **Should `Redux API Layer` be split into smaller, more focused modules?**
  _Cohesion score 0.05877167205406994 - nodes in this community are weakly interconnected._