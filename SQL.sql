USE [landing]
GO
/****** Object:  Table [dbo].[bot_other_requests]    Script Date: 6/28/2026 11:22:52 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bot_other_requests](
    [chat_id] [bigint] NOT NULL,
    [bot_name] [nvarchar](50) NULL,
    [message] [nvarchar](max) NULL,
    [updated_at] [datetime2](0) NOT NULL,
    CONSTRAINT [PK_bot_other_requests] PRIMARY KEY CLUSTERED
(
[chat_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
    ) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
    GO
/****** Object:  Table [dbo].[bot_user_states]    Script Date: 6/28/2026 11:22:52 AM ******/
    SET ANSI_NULLS ON
    GO
    SET QUOTED_IDENTIFIER ON
    GO
CREATE TABLE [dbo].[bot_user_states](
    [chat_id] [bigint] NOT NULL,
    [bot_name] [nvarchar](50) NULL,
    [state] [nvarchar](50) NULL,
    [name] [nvarchar](100) NULL,
    [phone] [nvarchar](20) NULL,
    [portfolio_value] [nvarchar](100) NULL,
    [last_transaction] [nvarchar](50) NULL,
    [followUpMessage] [tinyint] NOT NULL,
    [created_at] [datetime2](0) NULL,
    [updated_at] [datetime2](0) NOT NULL,
    CONSTRAINT [UQ_bot_user_states_chat_bot] UNIQUE NONCLUSTERED
(
    [chat_id] ASC,
[bot_name] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
    ) ON [PRIMARY]
    GO
/****** Object:  Table [dbo].[credit]    Script Date: 6/28/2026 11:22:52 AM ******/
    SET ANSI_NULLS ON
    GO
    SET QUOTED_IDENTIFIER ON
    GO
CREATE TABLE [dbo].[credit](
    [id] [int] IDENTITY(1,1) NOT NULL,
    [hasAccount] [nvarchar](30) NULL,
    [portfolioValue] [nvarchar](500) NULL,
    [number] [nvarchar](30) NULL,
    [fullname] [nvarchar](50) NULL,
    [last_transaction] [nvarchar](60) NULL,
    [created_at] [datetime2](0) NULL,
    [sent_at] [datetime2](0) NULL,
    [utm] [nvarchar](500) NULL,
    [origin] [nvarchar](20) NULL,
    [chat_id] [nvarchar](20) NULL,
    [crm_retry] [int] NOT NULL,
    [crm_request] [nvarchar](max) NULL,
    [crm_response] [nvarchar](max) NULL,
    [http_code] [int] NULL,
    [ip] [varchar](45) NULL,
    CONSTRAINT [PK_credit] PRIMARY KEY CLUSTERED
(
[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
    ) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
    GO
/****** Object:  Table [dbo].[gold]    Script Date: 6/28/2026 11:22:52 AM ******/
    SET ANSI_NULLS ON
    GO
    SET QUOTED_IDENTIFIER ON
    GO
CREATE TABLE [dbo].[gold](
    [id] [int] IDENTITY(1,1) NOT NULL,
    [number] [nvarchar](30) NULL,
    [name] [nvarchar](50) NULL,
    [utm] [nvarchar](max) NULL,
    [ip] [nvarchar](30) NULL,
    [ref] [nvarchar](500) NULL,
    [sent_at] [datetime2](0) NULL,
    [created_at] [datetime2](0) NULL,
    [origin] [nvarchar](20) NULL,
    [chat_id] [nvarchar](20) NULL,
    [crm_retry] [int] NOT NULL,
    [crm_request] [nvarchar](max) NULL,
    [crm_response] [nvarchar](max) NULL,
    [http_code] [int] NULL,
    [ipo] [bit] NOT NULL,
    CONSTRAINT [PK_gold] PRIMARY KEY CLUSTERED
(
[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
    ) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
    GO
/****** Object:  Table [dbo].[solar]    Script Date: 6/28/2026 11:22:52 AM ******/
    SET ANSI_NULLS ON
    GO
    SET QUOTED_IDENTIFIER ON
    GO
CREATE TABLE [dbo].[solar](
    [id] [int] IDENTITY(1,1) NOT NULL,
    [section] [nvarchar](500) NULL,
    [number] [nvarchar](30) NULL,
    [fullname] [nvarchar](50) NULL,
    [utm] [nvarchar](500) NULL,
    [sent_at] [datetime2](0) NULL,
    [created_at] [datetime2](0) NULL,
    [origin] [nvarchar](20) NULL,
    [chat_id] [nvarchar](20) NULL,
    [crm_retry] [int] NOT NULL,
    [crm_request] [nvarchar](max) NULL,
    [crm_response] [nvarchar](max) NULL,
    [http_code] [int] NULL,
    [ip] [varchar](45) NULL,
    CONSTRAINT [PK_solar] PRIMARY KEY CLUSTERED
(
[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
    ) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
    GO
ALTER TABLE [dbo].[bot_other_requests] ADD  CONSTRAINT [DF_bot_other_requests_updated_at]  DEFAULT (sysdatetime()) FOR [updated_at]
    GO
ALTER TABLE [dbo].[bot_user_states] ADD  CONSTRAINT [DF_bot_user_states_followUpMessage]  DEFAULT ((0)) FOR [followUpMessage]
    GO
ALTER TABLE [dbo].[bot_user_states] ADD  CONSTRAINT [DF_bot_user_states_updated_at]  DEFAULT (sysdatetime()) FOR [updated_at]
    GO
ALTER TABLE [dbo].[credit] ADD  CONSTRAINT [DF_credit_crm_retry]  DEFAULT ((0)) FOR [crm_retry]
    GO
ALTER TABLE [dbo].[gold] ADD  CONSTRAINT [DF_gold_crm_retry]  DEFAULT ((0)) FOR [crm_retry]
    GO
ALTER TABLE [dbo].[gold] ADD  CONSTRAINT [DF_gold_ipo]  DEFAULT ((0)) FOR [ipo]
    GO
ALTER TABLE [dbo].[solar] ADD  CONSTRAINT [DF_solar_crm_retry]  DEFAULT ((0)) FOR [crm_retry]
    GO
